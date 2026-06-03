<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Interfaces;

use App\Catalog\Domain\Entity\Movie;
use App\Catalog\Domain\ValueObject\TmdbId;
use App\Shared\Domain\ValueObject\Uuid;

interface MovieRepositoryInterface
{
    public function create(Movie $movie): void;

    public function update(Movie $movie): void;

    public function findByUuid(Uuid $uuid): ?Movie;

    public function findByTmdbId(TmdbId $tmdbId): ?Movie;

    public function existsByTmdbId(TmdbId $tmdbId): bool;

    /**
     * @param  list<Uuid>  $uuids
     * @return array<string, Movie> keyed por uuid string
     */
    public function findManyByUuids(array $uuids): array;

    /**
     * @return array{movies: list<Movie>, total: int, page: int, totalPages: int}
     */
    public function searchByFulltext(string $query, int $page, int $perPage): array;
}
