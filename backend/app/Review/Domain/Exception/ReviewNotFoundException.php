<?php

declare(strict_types=1);

namespace App\Review\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class ReviewNotFoundException extends NotFoundException
{
    public static function forUuid(Uuid $uuid): self
    {
        return new self(sprintf('Review with uuid "%s" not found.', $uuid->value()));
    }
}
