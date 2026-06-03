<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Exception;

use App\Catalog\Domain\ValueObject\TmdbId;
use App\Shared\Domain\Exception\ConflictException;

class MovieAlreadyExistsException extends ConflictException
{
    public static function forTmdbId(TmdbId $tmdbId): self
    {
        return new self(sprintf('Movie with TMDB id %d already exists.', $tmdbId->value()));
    }
}
