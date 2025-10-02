<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\Crypto;

use WhatsApp\StreamEncryption\Exception\EncryptionException;

/**
 * Шифрует данные с помощью AES-256-CBC
 */
class Encryptor
{
    private const CIPHER_METHOD = 'AES-256-CBC';

    /**
     * Зашифровать данные (AES-256-CBC)
     *
     * @param string $data Данные
     * @param string $cipherKey Ключ (32 байта)
     * @param string $iv IV (16 байт)
     * @return string Зашифрованные данные
     */
    public function encrypt(string $data, string $cipherKey, string $iv): string
    {
        if (strlen($cipherKey) !== 32) {
            throw new EncryptionException('Cipher key must be exactly 32 bytes');
        }

        if (strlen($iv) !== 16) {
            throw new EncryptionException('IV must be exactly 16 bytes');
        }

        $encrypted = openssl_encrypt(
            $data,
            self::CIPHER_METHOD,
            $cipherKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new EncryptionException('Encryption failed: ' . openssl_error_string());
        }

        return $encrypted;
    }
}
