<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\Stream;

use Psr\Http\Message\StreamInterface;
use WhatsApp\StreamEncryption\Crypto\Decryptor;
use WhatsApp\StreamEncryption\Crypto\MacGenerator;

/**
 * Декоратор PSR-7 потока, который дешифрует данные
 */
class DecryptingStream implements StreamInterface
{
    private const MAC_LENGTH = 10;

    private StreamInterface $stream;
    private Decryptor $decryptor;
    private MacGenerator $macGenerator;
    private string $cipherKey;
    private string $iv;
    private string $macKey;
    private ?string $decryptedCache = null;
    private bool $isClosed = false;

    public function __construct(
        StreamInterface $stream,
        Decryptor $decryptor,
        MacGenerator $macGenerator,
        string $cipherKey,
        string $iv,
        string $macKey
    ) {
        $this->stream = $stream;
        $this->decryptor = $decryptor;
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
        $this->decryptedCache = null;
    }

    public function detach()
    {
        $this->decryptedCache = null;
        return $this->stream->detach();
    }

    public function getSize(): ?int
    {
        // Размер зависит от паддинга
        return null;
    }

    public function tell(): int
    {
        return 0;
    }

    public function eof(): bool
    {
        return $this->isClosed || $this->decryptedCache !== null;
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
        $this->decryptedCache = null;
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
        if ($this->decryptedCache === null) {
            $this->decryptAll();
        }

        $data = substr($this->decryptedCache, 0, $length);
        $this->decryptedCache = substr($this->decryptedCache, $length);

        if ($this->decryptedCache === '') {
            $this->decryptedCache = null;
        }

        return $data;
    }

    public function getContents(): string
    {
        if ($this->decryptedCache === null) {
            $this->decryptAll();
        }

        $contents = $this->decryptedCache;
        $this->decryptedCache = null;
        return $contents;
    }

    public function getMetadata($key = null)
    {
        return $this->stream->getMetadata($key);
    }

    private function decryptAll(): void
    {
        $encryptedData = $this->stream->getContents();

        if (strlen($encryptedData) < self::MAC_LENGTH) {
            throw new \RuntimeException('Encrypted data is too short');
        }

        $encrypted = substr($encryptedData, 0, -self::MAC_LENGTH);
        $mac = substr($encryptedData, -self::MAC_LENGTH);

        $this->macGenerator->validate($this->iv . $encrypted, $mac, $this->macKey);
        $this->decryptedCache = $this->decryptor->decrypt($encrypted, $this->cipherKey, $this->iv);
    }
}
