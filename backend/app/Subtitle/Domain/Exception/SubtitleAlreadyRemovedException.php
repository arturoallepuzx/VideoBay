<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

class SubtitleAlreadyRemovedException extends ConflictException
{
    public static function forExternal(string $provider, string $externalId): self
    {
        return new self(sprintf('Subtitle "%s:%s" was removed by moderation.', $provider, $externalId));
    }
}
