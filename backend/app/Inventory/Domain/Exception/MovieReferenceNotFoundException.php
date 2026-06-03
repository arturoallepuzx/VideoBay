<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class MovieReferenceNotFoundException extends NotFoundException
{
    public static function forUuid(Uuid $movieId): self
    {
        return new self(sprintf('Referenced movie "%s" does not exist.', $movieId->value()));
    }
}
