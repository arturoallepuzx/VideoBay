<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Exception;

use App\Catalog\Domain\ValueObject\TmdbId;
use App\Shared\Domain\Exception\ConflictException;

class PersonAlreadyExistsException extends ConflictException
{
    public static function forTmdbId(TmdbId $tmdbId): self
    {
        return new self(sprintf('Person with TMDB id %d already exists.', $tmdbId->value()));
    }
}
