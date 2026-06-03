<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class SubtitleNotFoundException extends NotFoundException
{
    public static function forUuid(Uuid $uuid): self
    {
        return new self(sprintf('Subtitle "%s" not found.', $uuid->value()));
    }
}
