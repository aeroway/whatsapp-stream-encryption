<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\MediaType;

class ImageMediaType implements MediaTypeInterface
{
    public function getApplicationInfo(): string
    {
        return 'WhatsApp Image Keys';
    }

    public function isStreamable(): bool
    {
        return false;
    }
}
