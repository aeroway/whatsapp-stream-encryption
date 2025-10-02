<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\Crypto;

use WhatsApp\StreamEncryption\Exception\DecryptionException;

/**
 * Генерирует и проверяет подписи HMAC-SHA256
 */
class MacGenerator
{
    private const MAC_LENGTH = 10;

    /**
     * Сгенерировать MAC (HMAC-SHA256)
     *
     * @param string $data Данные
     * @param string $macKey Ключ (32 байта)
     * @return string MAC (10 байт)
     */
    public function generate(string $data, string $macKey): string
    {
        $hmac = hash_hmac('sha256', $data, $macKey, true);
        return substr($hmac, 0, self::MAC_LENGTH);
    }

    /**
     * Проверить MAC
     *
     * @param string $data Данные
     * @param string $mac Ожидаемый MAC (10 байт)
     * @param string $macKey Ключ (32 байта)
     * @throws DecryptionException
     */
    public function validate(string $data, string $mac, string $macKey): void
    {
        $expectedMac = $this->generate($data, $macKey);

        if (!hash_equals($expectedMac, $mac)) {
            throw new DecryptionException('MAC validation failed');
        }
    }
}
