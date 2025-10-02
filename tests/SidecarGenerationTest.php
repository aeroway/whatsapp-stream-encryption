<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\Tests;

use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use WhatsApp\StreamEncryption\Factory\StreamFactory;
use WhatsApp\StreamEncryption\MediaType\VideoMediaType;

class SidecarGenerationTest extends TestCase
{
    private StreamFactory $factory;
    private string $samplesDir;

    protected function setUp(): void
    {
        $this->factory = new StreamFactory();
        $this->samplesDir = __DIR__ . '/../samples';
    }

    public function testVideoSidecarGeneration(): void
    {
        $mediaKey = file_get_contents($this->samplesDir . '/VIDEO.key');
        $encrypted = file_get_contents($this->samplesDir . '/VIDEO.encrypted');
        $expectedSidecar = file_get_contents($this->samplesDir . '/VIDEO.sidecar');

        $encryptedStream = new Stream(fopen('php://temp', 'r+'));
        $encryptedStream->write($encrypted);
        $encryptedStream->rewind();

        $sidecarStream = $this->factory->createSidecarGeneratingStream(
            $encryptedStream,
            $mediaKey,
            new VideoMediaType()
        );

        $sidecarStream->getContents();

        $sidecar = $sidecarStream->getSidecar();

        $this->assertEquals($expectedSidecar, $sidecar, 'Generated sidecar should match expected');
    }

    public function testSidecarGenerationWithoutFullRead(): void
    {
        $mediaKey = $this->factory->generateMediaKey();
        $data = str_repeat('A', 100000); // 100KB of data

        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write($data);
        $stream->rewind();

        $sidecarStream = $this->factory->createSidecarGeneratingStream(
            $stream,
            $mediaKey,
            new VideoMediaType()
        );

        while (!$sidecarStream->eof()) {
            $sidecarStream->read(8192);
        }

        $sidecar = $sidecarStream->getSidecar();

        $this->assertNotEmpty($sidecar, 'Sidecar should be generated after reading stream');
    }
}

