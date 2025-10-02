<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\Crypto;

use WhatsApp\StreamEncryption\Exception\EncryptionException;

/**
 * Расширяет медиа-ключ с помощью HKDF-SHA256
 */
class KeyExpander
{
    private const EXPANDED_KEY_LENGTH = 112;

    /**
     * Расширить медиа-ключ с помощью HKDF-SHA256
     *
     * @param string $mediaKey Медиа-ключ (32 байта)
     * @param string $info Информационная строка для HKDF
     * @return array{iv: string, cipherKey: string, macKey: string, refKey: string}
     */
    public function expand(string $mediaKey, string $info): array
    {
        if (strlen($mediaKey) !== 32) {
            throw new EncryptionException('Media key must be exactly 32 bytes');
        }

        $expanded = hash_hkdf('sha256', $mediaKey, self::EXPANDED_KEY_LENGTH, $info, '');

        if (strlen($expanded) !== self::EXPANDED_KEY_LENGTH) {
            throw new EncryptionException('Failed to expand key to required length');
        }

        return [
            'iv' => substr($expanded, 0, 16),
            'cipherKey' => substr($expanded, 16, 32),
            'macKey' => substr($expanded, 48, 32),
            'refKey' => substr($expanded, 80, 32),
        ];
    }
}
