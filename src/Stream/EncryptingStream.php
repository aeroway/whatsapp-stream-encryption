<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\Stream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;
use WhatsApp\StreamEncryption\Crypto\Encryptor;
use WhatsApp\StreamEncryption\Crypto\MacGenerator;

/**
 * Декоратор PSR-7 потока для шифрования данных
 */
class EncryptingStream implements StreamInterface
{
    private const BUFFER_SIZE = 8192;

    private StreamInterface $stream;
    private Encryptor $encryptor;
    private MacGenerator $macGenerator;
    private string $cipherKey;
    private string $iv;
    private string $macKey;
    private string $outputBuffer = '';
    private bool $finalized = false;
    private int $position = 0;

    public function __construct(
        StreamInterface $stream,
        Encryptor $encryptor,
        MacGenerator $macGenerator,
        string $cipherKey,
        string $iv,
        string $macKey
    ) {
        $this->stream = $stream;
        $this->encryptor = $encryptor;
        $this->macGenerator = $macGenerator;
        $this->cipherKey = $cipherKey;
        $this->iv = $iv;
        $this->macKey = $macKey;
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
        $this->outputBuffer = '';
    }

    public function detach()
    {
        $this->outputBuffer = '';
        return $this->stream->detach();
    }

    public function getSize(): ?int
    {
        // Размер зависит от паддинга и MAC
        return null;
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->finalized && $this->outputBuffer === '';
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        throw new RuntimeException('Stream is not seekable');
    }

    public function rewind(): void
    {
        throw new RuntimeException('Cannot rewind encrypting stream');
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string): int
    {
        throw new RuntimeException('Stream is not writable');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        if (!$this->finalized) {
            $this->processStream();
        }

        $bytesToRead = min($length, strlen($this->outputBuffer));
        $data = substr($this->outputBuffer, 0, $bytesToRead);
        $this->outputBuffer = substr($this->outputBuffer, $bytesToRead);
        $this->position += $bytesToRead;

        return $data;
    }

    public function getContents(): string
    {
        if (!$this->finalized) {
            $this->processStream();
        }

        $contents = $this->outputBuffer;
        $this->position += strlen($contents);
        $this->outputBuffer = '';
        return $contents;
    }

    public function getMetadata($key = null)
    {
        return $this->stream->getMetadata($key);
    }

    private function processStream(): void
    {
        $plaintext = '';
        while (!$this->stream->eof()) {
            $chunk = $this->stream->read(self::BUFFER_SIZE);
            if ($chunk === '') {
                break;
            }
            $plaintext .= $chunk;
        }

        $encrypted = $this->encryptor->encrypt($plaintext, $this->cipherKey, $this->iv);
        $mac = $this->macGenerator->generate($this->iv . $encrypted, $this->macKey);
        $this->outputBuffer = $encrypted . $mac;
        $this->finalized = true;
    }
}
