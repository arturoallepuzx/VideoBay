<?php

declare(strict_types=1);

namespace App\Catalog\Application\ListPersonFilmography;

use App\Catalog\Application\ImportPersonFromTmdb\ImportPersonFromTmdb;
use App\Catalog\Domain\Entity\Person;
use App\Catalog\Domain\Exception\PersonNotFoundException;
use App\Catalog\Domain\Interfaces\PersonRepositoryInterface;
use App\Catalog\Domain\Interfaces\TmdbClientInterface;
use App\Catalog\Domain\ValueObject\TmdbId;
use App\Shared\Domain\ValueObject\Uuid;

class ListPersonFilmography
{
    public function __construct(
        private TmdbClientInterface $tmdbClient,
        private PersonRepositoryInterface $personRepository,
        private ImportPersonFromTmdb $importPersonFromTmdb,
    ) {}

    public function __invoke(string $identifier): ListPersonFilmographyResponse
    {
        $person = $this->resolvePerson($identifier);

        if ($person === null || $person->tmdbId() === null) {
            throw $this->notFoundFor($identifier);
        }

        $credits = $this->tmdbClient->getPersonMovieCredits($person->tmdbId()->value());

        return ListPersonFilmographyResponse::create($credits);
    }

    private function resolvePerson(string $identifier): ?Person
    {
        if (preg_match('/^\d+$/', $identifier) === 1) {
            $tmdbId = TmdbId::create((int) $identifier);
            $existing = $this->personRepository->findByTmdbId($tmdbId);
            if ($existing !== null) {
                return $existing;
            }

            ($this->importPersonFromTmdb)($tmdbId->value());

            return $this->personRepository->findByTmdbId($tmdbId);
        }

        return $this->personRepository->findByUuid(Uuid::create($identifier));
    }

    private function notFoundFor(string $identifier): PersonNotFoundException
    {
        if (preg_match('/^\d+$/', $identifier) === 1) {
            return PersonNotFoundException::forTmdbId(TmdbId::create((int) $identifier));
        }

        return PersonNotFoundException::forUuid(Uuid::create($identifier));
    }
}
