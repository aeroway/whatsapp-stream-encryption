<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\MediaType;

class AudioMediaType implements MediaTypeInterface
{
    public function getApplicationInfo(): string
    {
        return 'WhatsApp Audio Keys';
    }

    public function isStreamable(): bool
    {
        return true;
    }
}
