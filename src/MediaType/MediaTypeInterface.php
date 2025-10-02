<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\MediaType;

/**
 * Стратегия типа медиа для HKDF
 */
interface MediaTypeInterface
{
    /**
     * Получить информационную строку для HKDF
     *
     * @return string Например: "WhatsApp Image Keys"
     */
    public function getApplicationInfo(): string;

    /**
     * Поддерживает ли стриминг
     *
     * @return bool True для видео/аудио
     */
    public function isStreamable(): bool;
}
