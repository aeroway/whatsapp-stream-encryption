<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\Crypto;

use WhatsApp\StreamEncryption\Exception\DecryptionException;

/**
 * Дешифрует данные с помощью AES-256-CBC
 */
class Decryptor
{
    private const CIPHER_METHOD = 'AES-256-CBC';

    /**
     * Дешифровать данные (AES-256-CBC)
     *
     * @param string $encryptedData Зашифрованные данные
     * @param string $cipherKey Ключ (32 байта)
     * @param string $iv IV (16 байт)
     * @return string Дешифрованные данные
     */
    public function decrypt(string $encryptedData, string $cipherKey, string $iv): string
    {
        if (strlen($cipherKey) !== 32) {
            throw new DecryptionException('Cipher key must be exactly 32 bytes');
        }

        if (strlen($iv) !== 16) {
            throw new DecryptionException('IV must be exactly 16 bytes');
        }

        $decrypted = openssl_decrypt(
            $encryptedData,
            self::CIPHER_METHOD,
            $cipherKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new DecryptionException('Decryption failed: ' . openssl_error_string());
        }

        return $decrypted;
    }
}
