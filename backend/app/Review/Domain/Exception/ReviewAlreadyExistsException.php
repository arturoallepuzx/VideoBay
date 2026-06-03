<?php

declare(strict_types=1);

namespace App\Review\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;
use App\Shared\Domain\ValueObject\Uuid;

class ReviewAlreadyExistsException extends ConflictException
{
    public static function forUserAndMovie(Uuid $userId, Uuid $movieId): self
    {
        return new self(sprintf(
            'User "%s" already has a review for movie "%s".',
            $userId->value(),
            $movieId->value(),
        ));
    }
}
