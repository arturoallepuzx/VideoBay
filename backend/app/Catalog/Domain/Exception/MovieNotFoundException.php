<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Exception;

use App\Catalog\Domain\ValueObject\TmdbId;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class MovieNotFoundException extends NotFoundException
{
    public static function forUuid(Uuid $uuid): self
    {
        return new self(sprintf('Movie "%s" not found.', $uuid->value()));
    }

    public static function forTmdbId(TmdbId $tmdbId): self
    {
        return new self(sprintf('Movie with TMDB id %d not found.', $tmdbId->value()));
    }
}
