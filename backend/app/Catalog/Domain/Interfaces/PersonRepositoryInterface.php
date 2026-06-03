<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Interfaces;

use App\Catalog\Domain\Entity\Person;
use App\Catalog\Domain\ValueObject\TmdbId;
use App\Shared\Domain\ValueObject\Uuid;

interface PersonRepositoryInterface
{
    public function create(Person $person): void;

    public function update(Person $person): void;

    public function findByUuid(Uuid $uuid): ?Person;

    public function findByTmdbId(TmdbId $tmdbId): ?Person;

    public function existsByTmdbId(TmdbId $tmdbId): bool;

    /**
     * @param  list<Uuid>  $uuids
     * @return array<string, Person> keyed por uuid string
     */
    public function findManyByUuids(array $uuids): array;

    /**
     * @return array{people: list<Person>, total: int, page: int, totalPages: int}
     */
    public function searchByFulltext(string $query, int $page, int $perPage): array;
}
