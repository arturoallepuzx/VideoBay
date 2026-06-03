<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Interfaces;

use App\Catalog\Domain\Entity\Genre;
use App\Catalog\Domain\ValueObject\GenreName;
use App\Catalog\Domain\ValueObject\TmdbId;
use App\Shared\Domain\ValueObject\Uuid;

interface GenreRepositoryInterface
{
    public function findByTmdbId(TmdbId $tmdbId): ?Genre;

    public function findOrCreate(TmdbId $tmdbId, GenreName $name): Genre;

    public function attachToMovie(Uuid $movieUuid, Genre $genre): void;

    public function detachAllFromMovie(Uuid $movieUuid): void;

    /**
     * @return list<Genre>
     */
    public function findByMovieUuid(Uuid $movieUuid): array;
}
