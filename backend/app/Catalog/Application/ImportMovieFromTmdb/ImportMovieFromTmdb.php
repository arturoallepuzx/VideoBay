<?php

declare(strict_types=1);

namespace App\Catalog\Application\ImportMovieFromTmdb;

use App\Catalog\Domain\Entity\Movie;
use App\Catalog\Domain\Entity\MovieCredit;
use App\Catalog\Domain\Entity\Person;
use App\Catalog\Domain\Exception\MovieAlreadyExistsException;
use App\Catalog\Domain\Exception\PersonAlreadyExistsException;
use App\Catalog\Domain\Interfaces\GenreRepositoryInterface;
use App\Catalog\Domain\Interfaces\MovieCreditRepositoryInterface;
use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Catalog\Domain\Interfaces\PersonRepositoryInterface;
use App\Catalog\Domain\Interfaces\TmdbClientInterface;
use App\Catalog\Domain\ValueObject\CreditDepartment;
use App\Catalog\Domain\ValueObject\GenreName;
use App\Catalog\Domain\ValueObject\ImagePath;
use App\Catalog\Domain\ValueObject\ImdbId;
use App\Catalog\Domain\ValueObject\LanguageCode;
use App\Catalog\Domain\ValueObject\MovieTitle;
use App\Catalog\Domain\ValueObject\Overview;
use App\Catalog\Domain\ValueObject\PersonName;
use App\Catalog\Domain\ValueObject\RuntimeMinutes;
use App\Catalog\Domain\ValueObject\TmdbId;
use App\Catalog\Domain\ValueObject\TmdbRating;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\DomainDateTime;

class ImportMovieFromTmdb
{
    public function __construct(
        private TmdbClientInterface $tmdbClient,
        private MovieRepositoryInterface $movieRepository,
        private PersonRepositoryInterface $personRepository,
        private GenreRepositoryInterface $genreRepository,
        private MovieCreditRepositoryInterface $creditRepository,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(int $tmdbId): ImportMovieFromTmdbResponse
    {
        $tmdbData = $this->tmdbClient->getMovieDetail($tmdbId);

        return $this->transactionRunner->run(
            fn (): ImportMovieFromTmdbResponse => $this->persistAll($tmdbData, $tmdbId),
        );
    }

    /** @param array<string, mixed> $tmdbData */
    private function persistAll(array $tmdbData, int $tmdbId): ImportMovieFromTmdbResponse
    {
        $movie = $this->upsertMovie($tmdbData, TmdbId::create($tmdbId));
        $this->syncGenres($movie, $tmdbData);
        $this->syncCredits($movie, $tmdbData);

        return ImportMovieFromTmdbResponse::create($movie);
    }

    /** @param array<string, mixed> $tmdbData */
    private function upsertMovie(array $tmdbData, TmdbId $tmdbId): Movie
    {
        $existing = $this->movieRepository->findByTmdbId($tmdbId);

        if ($existing !== null) {
            $existing->refreshFromTmdb(
                $this->parseImdbId($tmdbData),
                MovieTitle::create((string) ($tmdbData['title'] ?? '')),
                $this->parseOptionalMovieTitle($tmdbData['original_title'] ?? null),
                $this->parseOptionalOverview($tmdbData['overview'] ?? null),
                $this->parseOptionalDate($tmdbData['release_date'] ?? null),
                $this->parseOptionalRuntime($tmdbData['runtime'] ?? null),
                $this->parseOptionalLanguage($tmdbData['original_language'] ?? null),
                $this->parseOptionalImagePath($tmdbData['poster_path'] ?? null),
                $this->parseOptionalImagePath($tmdbData['backdrop_path'] ?? null),
                $this->parseOptionalRating($tmdbData['vote_average'] ?? null),
            );
            $this->movieRepository->update($existing);

            return $existing;
        }

        $movie = Movie::dddCreate(
            $tmdbId,
            $this->parseImdbId($tmdbData),
            MovieTitle::create((string) ($tmdbData['title'] ?? '')),
            $this->parseOptionalMovieTitle($tmdbData['original_title'] ?? null),
            $this->parseOptionalOverview($tmdbData['overview'] ?? null),
            $this->parseOptionalDate($tmdbData['release_date'] ?? null),
            $this->parseOptionalRuntime($tmdbData['runtime'] ?? null),
            $this->parseOptionalLanguage($tmdbData['original_language'] ?? null),
            $this->parseOptionalImagePath($tmdbData['poster_path'] ?? null),
            $this->parseOptionalImagePath($tmdbData['backdrop_path'] ?? null),
            $this->parseOptionalRating($tmdbData['vote_average'] ?? null),
        );

        try {
            $this->movieRepository->create($movie);

            return $movie;
        } catch (MovieAlreadyExistsException) {
            $reloaded = $this->movieRepository->findByTmdbId($tmdbId);
            if ($reloaded === null) {
                throw new \RuntimeException(sprintf('Movie tmdb_id %d disappeared after conflict.', $tmdbId->value()));
            }

            return $reloaded;
        }
    }

    /** @param array<string, mixed> $tmdbData */
    private function syncGenres(Movie $movie, array $tmdbData): void
    {
        $this->genreRepository->detachAllFromMovie($movie->id());

        $genres = is_array($tmdbData['genres'] ?? null) ? $tmdbData['genres'] : [];
        foreach ($genres as $genreData) {
            if (! is_array($genreData) || ! isset($genreData['id'], $genreData['name'])) {
                continue;
            }

            $genre = $this->genreRepository->findOrCreate(
                TmdbId::create((int) $genreData['id']),
                GenreName::create((string) $genreData['name']),
            );
            $this->genreRepository->attachToMovie($movie->id(), $genre);
        }
    }

    /** @param array<string, mixed> $tmdbData */
    private function syncCredits(Movie $movie, array $tmdbData): void
    {
        $this->creditRepository->deleteByMovieUuid($movie->id());

        $credits = is_array($tmdbData['credits'] ?? null) ? $tmdbData['credits'] : [];

        foreach (is_array($credits['cast'] ?? null) ? $credits['cast'] : [] as $entry) {
            if (! is_array($entry) || ! isset($entry['id'], $entry['name'])) {
                continue;
            }

            $person = $this->upsertPersonBasic($entry);
            $this->creditRepository->create(
                MovieCredit::dddCreate(
                    $movie->id(),
                    $person->id(),
                    CreditDepartment::create((string) ($entry['known_for_department'] ?? 'Acting')),
                    null,
                    isset($entry['character']) && $entry['character'] !== '' ? (string) $entry['character'] : null,
                    isset($entry['order']) ? (int) $entry['order'] : null,
                ),
            );
        }

        foreach (is_array($credits['crew'] ?? null) ? $credits['crew'] : [] as $entry) {
            if (! is_array($entry) || ! isset($entry['id'], $entry['name'], $entry['department'])) {
                continue;
            }

            $person = $this->upsertPersonBasic($entry);
            $this->creditRepository->create(
                MovieCredit::dddCreate(
                    $movie->id(),
                    $person->id(),
                    CreditDepartment::create((string) $entry['department']),
                    isset($entry['job']) && $entry['job'] !== '' ? (string) $entry['job'] : null,
                    null,
                    null,
                ),
            );
        }
    }

    /** @param array<string, mixed> $entry */
    private function upsertPersonBasic(array $entry): Person
    {
        $tmdbId = TmdbId::create((int) $entry['id']);
        $existing = $this->personRepository->findByTmdbId($tmdbId);

        if ($existing !== null) {
            return $existing;
        }

        $person = Person::dddCreate(
            $tmdbId,
            PersonName::create((string) $entry['name']),
            null,
            $this->parseOptionalImagePath($entry['profile_path'] ?? null),
        );

        try {
            $this->personRepository->create($person);

            return $person;
        } catch (PersonAlreadyExistsException) {
            $reloaded = $this->personRepository->findByTmdbId($tmdbId);
            if ($reloaded === null) {
                throw new \RuntimeException(sprintf('Person tmdb_id %d disappeared after conflict.', $tmdbId->value()));
            }

            return $reloaded;
        }
    }

    /** @param array<string, mixed> $tmdbData */
    private function parseImdbId(array $tmdbData): ?ImdbId
    {
        $externalIds = $tmdbData['external_ids'] ?? null;
        if (! is_array($externalIds)) {
            return null;
        }

        $value = $externalIds['imdb_id'] ?? null;
        if (! is_string($value) || $value === '') {
            return null;
        }

        return ImdbId::create($value);
    }

    private function parseOptionalMovieTitle(mixed $value): ?MovieTitle
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return MovieTitle::create($value);
    }

    private function parseOptionalOverview(mixed $value): ?Overview
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Overview::create($value);
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

    private function parseOptionalRuntime(mixed $value): ?RuntimeMinutes
    {
        if (! is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return RuntimeMinutes::create((int) $value);
    }

    private function parseOptionalLanguage(mixed $value): ?LanguageCode
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return LanguageCode::create($value);
        } catch (\InvalidArgumentException) {
            return null;
        }
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

    private function parseOptionalRating(mixed $value): ?TmdbRating
    {
        if (! is_numeric($value)) {
            return null;
        }

        $float = (float) $value;
        if ($float < 0.0 || $float > 10.0) {
            return null;
        }

        return TmdbRating::create($float);
    }
}
