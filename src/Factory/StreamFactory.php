<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\Factory;

use Psr\Http\Message\StreamInterface;
use WhatsApp\StreamEncryption\Crypto\Decryptor;
use WhatsApp\StreamEncryption\Crypto\Encryptor;
use WhatsApp\StreamEncryption\Crypto\KeyExpander;
use WhatsApp\StreamEncryption\Crypto\MacGenerator;
use WhatsApp\StreamEncryption\MediaType\MediaTypeInterface;
use WhatsApp\StreamEncryption\Stream\DecryptingStream;
use WhatsApp\StreamEncryption\Stream\EncryptingStream;
use WhatsApp\StreamEncryption\Stream\SidecarGeneratingStream;

/**
 * Фабрика для создания декораторов потоков шифрования/дешифрования
 */
class StreamFactory
{
    private KeyExpander $keyExpander;
    private Encryptor $encryptor;
    private Decryptor $decryptor;
    private MacGenerator $macGenerator;

    public function __construct(
        ?KeyExpander $keyExpander = null,
        ?Encryptor $encryptor = null,
        ?Decryptor $decryptor = null,
        ?MacGenerator $macGenerator = null
    ) {
        $this->keyExpander = $keyExpander ?? new KeyExpander();
        $this->encryptor = $encryptor ?? new Encryptor();
        $this->decryptor = $decryptor ?? new Decryptor();
        $this->macGenerator = $macGenerator ?? new MacGenerator();
    }

    /**
     * Создать декоратор шифрующего потока
     *
     * @param StreamInterface $stream Исходный поток
     * @param string $mediaKey Медиа-ключ (32 байта)
     * @param MediaTypeInterface $mediaType Тип медиа
     * @return EncryptingStream
     */
    public function createEncryptingStream(
        StreamInterface $stream,
        string $mediaKey,
        MediaTypeInterface $mediaType
    ): EncryptingStream {
        $keys = $this->keyExpander->expand($mediaKey, $mediaType->getApplicationInfo());

        return new EncryptingStream(
            $stream,
            $this->encryptor,
            $this->macGenerator,
            $keys['cipherKey'],
            $keys['iv'],
            $keys['macKey']
        );
    }

    /**
     * Создать декоратор дешифрующего потока
     *
     * @param StreamInterface $stream Зашифрованный поток
     * @param string $mediaKey Медиа-ключ (32 байта)
     * @param MediaTypeInterface $mediaType Тип медиа
     * @return DecryptingStream
     */
    public function createDecryptingStream(
        StreamInterface $stream,
        string $mediaKey,
        MediaTypeInterface $mediaType
    ): DecryptingStream {
        $keys = $this->keyExpander->expand($mediaKey, $mediaType->getApplicationInfo());

        return new DecryptingStream(
            $stream,
            $this->decryptor,
            $this->macGenerator,
            $keys['cipherKey'],
            $keys['iv'],
            $keys['macKey']
        );
    }

    /**
     * Создать декоратор генерации sidecar для стриминга
     *
     * @param StreamInterface $stream Зашифрованный поток
     * @param string $mediaKey Медиа-ключ (32 байта)
     * @param MediaTypeInterface $mediaType Тип медиа
     * @return SidecarGeneratingStream
     */
    public function createSidecarGeneratingStream(
        StreamInterface $stream,
        string $mediaKey,
        MediaTypeInterface $mediaType
    ): SidecarGeneratingStream {
        $keys = $this->keyExpander->expand($mediaKey, $mediaType->getApplicationInfo());

        return new SidecarGeneratingStream(
            $stream,
            $this->macGenerator,
            $keys['macKey'],
            $keys['iv']
        );
    }

    /**
     * Сгенерировать случайный медиа-ключ
     *
     * @return string Ключ (32 байта)
     * @throws \Exception
     */
    public function generateMediaKey(): string
    {
        return random_bytes(32);
    }
}

