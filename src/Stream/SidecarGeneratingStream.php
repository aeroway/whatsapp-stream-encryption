<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\Stream;

use Psr\Http\Message\StreamInterface;
use Throwable;
use WhatsApp\StreamEncryption\Crypto\MacGenerator;

/**
 * Декоратор PSR-7 потока, который генерирует sidecar данные для стриминга
 * Генерирует MAC для каждого 64KB чанка без дополнительных чтений из потока
 */
class SidecarGeneratingStream implements StreamInterface
{
    private const CHUNK_SIZE = 65536; // 64KB
    private const IV_SIZE = 16;
    private const MAC_LENGTH = 10;

    private StreamInterface $stream;
    private MacGenerator $macGenerator;
    private string $macKey;
    private string $iv;
    private string $sidecar = '';
    private string $buffer = '';
    private int $position = 0;
    private bool $sidecarComplete = false;
    private int $processedBytes = 0;

    public function __construct(
        StreamInterface $stream,
        MacGenerator $macGenerator,
        string $macKey,
        string $iv
    ) {
        $this->stream = $stream;
        $this->macGenerator = $macGenerator;
        $this->macKey = $macKey;
        $this->iv = $iv;
    }

    /**
     * Получить сгенерированные sidecar данные
     *
     * @return string Бинарные sidecar данные
     */
    public function getSidecar(): string
    {
        return $this->sidecar;
    }

    public function __toString(): string
    {
        try {
            return $this->getContents();
        } catch (Throwable $e) {
            return '';
        }
    }

    public function close(): void
    {
        $this->stream->close();
    }

    public function detach()
    {
        return $this->stream->detach();
    }

    public function getSize(): ?int
    {
        return $this->stream->getSize();
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->stream->eof();
    }

    public function isSeekable(): bool
    {
        return $this->stream->isSeekable();
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        $this->stream->seek($offset, $whence);
        $this->position = $this->stream->tell();
        $this->buffer = '';
        $this->sidecar = '';
        $this->sidecarComplete = false;
        $this->processedBytes = 0;
    }

    public function rewind(): void
    {
        $this->stream->rewind();
        $this->position = 0;
        $this->buffer = '';
        $this->sidecar = '';
        $this->sidecarComplete = false;
        $this->processedBytes = 0;
    }

    public function isWritable(): bool
    {
        return $this->stream->isWritable();
    }

    public function write($string): int
    {
        $written = $this->stream->write($string);
        $this->position += $written;
        return $written;
    }

    public function isReadable(): bool
    {
        return $this->stream->isReadable();
    }

    public function read($length): string
    {
        $data = $this->stream->read($length);

        if ($data === '') {
            if (!$this->sidecarComplete) {
                $this->finalizeSidecar();
            }
            return '';
        }

        $this->buffer .= $data;

        // Обрабатываем полные чанки (64KB + 16 байт IV)
        while (strlen($this->buffer) >= self::CHUNK_SIZE + self::IV_SIZE) {
            $this->processChunk();
        }

        if ($this->stream->eof() && !$this->sidecarComplete) {
            $this->finalizeSidecar();
        }

        $this->position += strlen($data);
        return $data;
    }

    public function getContents(): string
    {
        $contents = '';
        while (!$this->eof()) {
            $chunk = $this->read(8192);
            if ($chunk === '') {
                break;
            }
            $contents .= $chunk;
        }

        if (!$this->sidecarComplete) {
            $this->finalizeSidecar();
        }

        return $contents;
    }

    public function getMetadata($key = null)
    {
        return $this->stream->getMetadata($key);
    }

    /**
     * Обработать чанк и сгенерировать MAC
     * 
     * Первый чанк: HMAC(IV_ключей + данные[0:64K])
     * Последующие: HMAC(последние_16_байт + данные[N*64K:(N+1)*64K])
     */
    private function processChunk(): void
    {
        if ($this->processedBytes === 0) {
            // Первый чанк
            $chunkData = substr($this->buffer, 0, self::CHUNK_SIZE);
            $chunkIv = $this->iv;
            $mac = $this->macGenerator->generate($chunkIv . $chunkData, $this->macKey);
            $this->sidecar .= $mac;

            // Сохраняем перекрытие для следующего IV
            $this->buffer = substr($this->buffer, self::CHUNK_SIZE - self::IV_SIZE);
            $this->processedBytes += self::CHUNK_SIZE;
        } else {
            // Последующие чанки с IV из предыдущего блока
            $chunkIv = substr($this->buffer, 0, self::IV_SIZE);
            $chunkData = substr($this->buffer, self::IV_SIZE, self::CHUNK_SIZE);
            $mac = $this->macGenerator->generate($chunkIv . $chunkData, $this->macKey);
            $this->sidecar .= $mac;

            $this->buffer = substr($this->buffer, self::CHUNK_SIZE);
            $this->processedBytes += self::CHUNK_SIZE;
        }
    }

    /**
     * Обработать последний неполный чанк
     */
    private function finalizeSidecar(): void
    {
        // ВАЖНО: буфер включает финальный MAC из encrypted файла
        if ($this->buffer !== '') {
            if ($this->processedBytes === 0) {
                // Файл < 64KB
                $chunkIv = $this->iv;
                $chunkData = $this->buffer;
            } else {
                // Извлекаем IV и оставшиеся данные
                if (strlen($this->buffer) > self::IV_SIZE) {
                    $chunkIv = substr($this->buffer, 0, self::IV_SIZE);
                    $chunkData = substr($this->buffer, self::IV_SIZE);
                } else {
                    $chunkIv = $this->iv;
                    $chunkData = $this->buffer;
                }
            }

            $mac = $this->macGenerator->generate($chunkIv . $chunkData, $this->macKey);
            $this->sidecar .= $mac;
        }
        $this->sidecarComplete = true;
    }
}
