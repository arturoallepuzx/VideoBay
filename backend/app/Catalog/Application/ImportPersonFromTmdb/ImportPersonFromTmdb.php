<?php

declare(strict_types=1);

namespace App\Catalog\Application\ImportPersonFromTmdb;

use App\Catalog\Domain\Entity\Person;
use App\Catalog\Domain\Exception\PersonAlreadyExistsException;
use App\Catalog\Domain\Interfaces\PersonRepositoryInterface;
use App\Catalog\Domain\Interfaces\TmdbClientInterface;
use App\Catalog\Domain\ValueObject\ImagePath;
use App\Catalog\Domain\ValueObject\Overview;
use App\Catalog\Domain\ValueObject\PersonName;
use App\Catalog\Domain\ValueObject\TmdbId;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\DomainDateTime;

class ImportPersonFromTmdb
{
    public function __construct(
        private TmdbClientInterface $tmdbClient,
        private PersonRepositoryInterface $personRepository,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(int $tmdbId): ImportPersonFromTmdbResponse
    {
        $tmdbData = $this->tmdbClient->getPersonDetail($tmdbId);

        return $this->transactionRunner->run(
            fn (): ImportPersonFromTmdbResponse => ImportPersonFromTmdbResponse::create(
                $this->upsertPerson($tmdbData, TmdbId::create($tmdbId)),
            ),
        );
    }

    /** @param array<string, mixed> $tmdbData */
    private function upsertPerson(array $tmdbData, TmdbId $tmdbId): Person
    {
        $name = PersonName::create((string) ($tmdbData['name'] ?? ''));
        $biography = $this->parseOptionalOverview($tmdbData['biography'] ?? null);
        $profilePath = $this->parseOptionalImagePath($tmdbData['profile_path'] ?? null);
        $birthday = $this->parseOptionalDate($tmdbData['birthday'] ?? null);
        $deathday = $this->parseOptionalDate($tmdbData['deathday'] ?? null);
        $placeOfBirth = $this->parseOptionalString($tmdbData['place_of_birth'] ?? null);

        $existing = $this->personRepository->findByTmdbId($tmdbId);

        if ($existing !== null) {
            $existing->refreshFromTmdb($name, $biography, $profilePath, $birthday, $deathday, $placeOfBirth);
            $this->personRepository->update($existing);

            return $existing;
        }

        $person = Person::dddCreate(
            $tmdbId,
            $name,
            $biography,
            $profilePath,
            $birthday,
            $deathday,
            $placeOfBirth,
        );

        try {
            $this->personRepository->create($person);

            return $person;
        } catch (PersonAlreadyExistsException) {
            $reloaded = $this->personRepository->findByTmdbId($tmdbId);
            if ($reloaded === null) {
                throw new \RuntimeException(sprintf('Person tmdb_id %d disappeared after conflict.', $tmdbId->value()));
            }
            $reloaded->refreshFromTmdb($name, $biography, $profilePath, $birthday, $deathday, $placeOfBirth);
            $this->personRepository->update($reloaded);

            return $reloaded;
        }
    }

    private function parseOptionalOverview(mixed $value): ?Overview
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Overview::create($value);
    }

    private function parseOptionalImagePath(mixed $value): ?ImagePath
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return ImagePath::create($value);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private function parseOptionalDate(mixed $value): ?DomainDateTime
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return DomainDateTime::create(new \DateTimeImmutable($value));
        } catch (\Exception) {
            return null;
        }
    }

    private function parseOptionalString(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }
}
