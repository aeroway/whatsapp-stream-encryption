<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\Stream;

use Psr\Http\Message\StreamInterface;
use WhatsApp\StreamEncryption\Crypto\Encryptor;
use WhatsApp\StreamEncryption\Crypto\MacGenerator;

/**
 * Декоратор PSR-7 потока, который шифрует данные
 */
class EncryptingStream implements StreamInterface
{
    private StreamInterface $stream;
    private Encryptor $encryptor;
    private MacGenerator $macGenerator;
    private string $cipherKey;
    private string $iv;
    private string $macKey;
    private ?string $encryptedCache = null;
    private bool $isClosed = false;

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
            $this->rewind();
            return $this->getContents();
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function close(): void
    {
        $this->stream->close();
        $this->isClosed = true;
        $this->encryptedCache = null;
    }

    public function detach()
    {
        $this->encryptedCache = null;
        return $this->stream->detach();
    }

    public function getSize(): ?int
    {
        // Размер зависит от паддинга и MAC
        return null;
    }

    public function tell(): int
    {
        return 0;
    }

    public function eof(): bool
    {
        return $this->isClosed || $this->encryptedCache !== null;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        throw new \RuntimeException('Stream is not seekable');
    }

    public function rewind(): void
    {
        $this->encryptedCache = null;
        $this->stream->rewind();
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string): int
    {
        throw new \RuntimeException('Stream is not writable');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        if ($this->encryptedCache === null) {
            $this->encryptAll();
        }

        $data = substr($this->encryptedCache, 0, $length);
        $this->encryptedCache = substr($this->encryptedCache, $length);

        if ($this->encryptedCache === '') {
            $this->encryptedCache = null;
        }

        return $data;
    }

    public function getContents(): string
    {
        if ($this->encryptedCache === null) {
            $this->encryptAll();
        }

        $contents = $this->encryptedCache;
        $this->encryptedCache = null;
        return $contents;
    }

    public function getMetadata($key = null)
    {
        return $this->stream->getMetadata($key);
    }

    private function encryptAll(): void
    {
        $plaintext = $this->stream->getContents();
        $encrypted = $this->encryptor->encrypt($plaintext, $this->cipherKey, $this->iv);
        $mac = $this->macGenerator->generate($this->iv . $encrypted, $this->macKey);
        $this->encryptedCache = $encrypted . $mac;
    }
}
