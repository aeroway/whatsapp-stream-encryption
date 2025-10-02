<?php

declare(strict_types=1);

namespace WhatsApp\StreamEncryption\MediaType;

class DocumentMediaType implements MediaTypeInterface
{
    public function getApplicationInfo(): string
    {
        return 'WhatsApp Document Keys';
    }

    public function isStreamable(): bool
    {
        return false;
    }
}
