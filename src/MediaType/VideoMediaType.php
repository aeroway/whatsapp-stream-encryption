<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\MediaType;

class VideoMediaType implements MediaTypeInterface
{
    public function getApplicationInfo(): string
    {
        return 'WhatsApp Video Keys';
    }

    public function isStreamable(): bool
    {
        return true;
    }
}
