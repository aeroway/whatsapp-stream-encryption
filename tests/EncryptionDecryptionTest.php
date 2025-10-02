<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\Tests;

use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use WhatsApp\StreamEncryption\Factory\StreamFactory;
use WhatsApp\StreamEncryption\MediaType\AudioMediaType;
use WhatsApp\StreamEncryption\MediaType\ImageMediaType;
use WhatsApp\StreamEncryption\MediaType\VideoMediaType;

class EncryptionDecryptionTest extends TestCase
{
    private StreamFactory $factory;
    private string $samplesDir;

    protected function setUp(): void
    {
        $this->factory = new StreamFactory();
        $this->samplesDir = __DIR__ . '/../samples';
    }

    public function testImageEncryption(): void
    {
        $mediaKey = file_get_contents($this->samplesDir . '/IMAGE.key');
        $original = file_get_contents($this->samplesDir . '/IMAGE.original');
        $expectedEncrypted = file_get_contents($this->samplesDir . '/IMAGE.encrypted');

        $sourceStream = new Stream(fopen('php://temp', 'r+'));
        $sourceStream->write($original);
        $sourceStream->rewind();

        $encryptingStream = $this->factory->createEncryptingStream(
            $sourceStream,
            $mediaKey,
            new ImageMediaType()
        );

        $encrypted = $encryptingStream->getContents();

        $this->assertEquals($expectedEncrypted, $encrypted, 'Encrypted data should match expected');
    }

    public function testImageDecryption(): void
    {
        $mediaKey = file_get_contents($this->samplesDir . '/IMAGE.key');
        $encrypted = file_get_contents($this->samplesDir . '/IMAGE.encrypted');
        $expectedOriginal = file_get_contents($this->samplesDir . '/IMAGE.original');

        $encryptedStream = new Stream(fopen('php://temp', 'r+'));
        $encryptedStream->write($encrypted);
        $encryptedStream->rewind();

        $decryptingStream = $this->factory->createDecryptingStream(
            $encryptedStream,
            $mediaKey,
            new ImageMediaType()
        );

        $decrypted = $decryptingStream->getContents();

        $this->assertEquals($expectedOriginal, $decrypted, 'Decrypted data should match original');
    }

    public function testAudioEncryption(): void
    {
        $mediaKey = file_get_contents($this->samplesDir . '/AUDIO.key');
        $original = file_get_contents($this->samplesDir . '/AUDIO.original');
        $expectedEncrypted = file_get_contents($this->samplesDir . '/AUDIO.encrypted');

        $sourceStream = new Stream(fopen('php://temp', 'r+'));
        $sourceStream->write($original);
        $sourceStream->rewind();

        $encryptingStream = $this->factory->createEncryptingStream(
            $sourceStream,
            $mediaKey,
            new AudioMediaType()
        );

        $encrypted = $encryptingStream->getContents();

        $this->assertEquals($expectedEncrypted, $encrypted, 'Encrypted audio should match expected');
    }

    public function testAudioDecryption(): void
    {
        $mediaKey = file_get_contents($this->samplesDir . '/AUDIO.key');
        $encrypted = file_get_contents($this->samplesDir . '/AUDIO.encrypted');
        $expectedOriginal = file_get_contents($this->samplesDir . '/AUDIO.original');

        $encryptedStream = new Stream(fopen('php://temp', 'r+'));
        $encryptedStream->write($encrypted);
        $encryptedStream->rewind();

        $decryptingStream = $this->factory->createDecryptingStream(
            $encryptedStream,
            $mediaKey,
            new AudioMediaType()
        );

        $decrypted = $decryptingStream->getContents();

        $this->assertEquals($expectedOriginal, $decrypted, 'Decrypted audio should match original');
    }

    public function testVideoEncryption(): void
    {
        $mediaKey = file_get_contents($this->samplesDir . '/VIDEO.key');
        $original = file_get_contents($this->samplesDir . '/VIDEO.original');
        $expectedEncrypted = file_get_contents($this->samplesDir . '/VIDEO.encrypted');

        $sourceStream = new Stream(fopen('php://temp', 'r+'));
        $sourceStream->write($original);
        $sourceStream->rewind();

        $encryptingStream = $this->factory->createEncryptingStream(
            $sourceStream,
            $mediaKey,
            new VideoMediaType()
        );

        $encrypted = $encryptingStream->getContents();

        $this->assertEquals($expectedEncrypted, $encrypted, 'Encrypted video should match expected');
    }

    public function testVideoDecryption(): void
    {
        $mediaKey = file_get_contents($this->samplesDir . '/VIDEO.key');
        $encrypted = file_get_contents($this->samplesDir . '/VIDEO.encrypted');
        $expectedOriginal = file_get_contents($this->samplesDir . '/VIDEO.original');

        $encryptedStream = new Stream(fopen('php://temp', 'r+'));
        $encryptedStream->write($encrypted);
        $encryptedStream->rewind();

        $decryptingStream = $this->factory->createDecryptingStream(
            $encryptedStream,
            $mediaKey,
            new VideoMediaType()
        );

        $decrypted = $decryptingStream->getContents();

        $this->assertEquals($expectedOriginal, $decrypted, 'Decrypted video should match original');
    }

    public function testRoundTrip(): void
    {
        $mediaKey = $this->factory->generateMediaKey();
        $originalData = 'Test data for encryption and decryption';

        $sourceStream = new Stream(fopen('php://temp', 'r+'));
        $sourceStream->write($originalData);
        $sourceStream->rewind();

        $encryptingStream = $this->factory->createEncryptingStream(
            $sourceStream,
            $mediaKey,
            new ImageMediaType()
        );

        $encrypted = $encryptingStream->getContents();

        $encryptedStream = new Stream(fopen('php://temp', 'r+'));
        $encryptedStream->write($encrypted);
        $encryptedStream->rewind();

        $decryptingStream = $this->factory->createDecryptingStream(
            $encryptedStream,
            $mediaKey,
            new ImageMediaType()
        );

        $decrypted = $decryptingStream->getContents();

        $this->assertEquals($originalData, $decrypted, 'Round-trip encryption/decryption should preserve data');
    }
}

