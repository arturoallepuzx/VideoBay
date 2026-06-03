<?php

declare(strict_types=1);

namespace App\Catalog\Application\GetMovieDetail;

use App\Catalog\Application\ImportMovieFromTmdb\ImportMovieFromTmdb;
use App\Catalog\Domain\Entity\Movie;
use App\Catalog\Domain\Exception\MovieNotFoundException;
use App\Catalog\Domain\Interfaces\GenreRepositoryInterface;
use App\Catalog\Domain\Interfaces\MovieCreditRepositoryInterface;
use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Catalog\Domain\Interfaces\PersonRepositoryInterface;
use App\Catalog\Domain\Interfaces\StreamableMovieFinderInterface;
use App\Catalog\Domain\ValueObject\TmdbId;
use App\Shared\Domain\ValueObject\Uuid;

class GetMovieDetail
{
    public function __construct(
        private MovieRepositoryInterface $movieRepository,
        private GenreRepositoryInterface $genreRepository,
        private MovieCreditRepositoryInterface $creditRepository,
        private PersonRepositoryInterface $personRepository,
        private ImportMovieFromTmdb $importMovieFromTmdb,
        private StreamableMovieFinderInterface $streamableFinder,
    ) {}

    public function __invoke(string $identifier): GetMovieDetailResponse
    {
        $movie = $this->resolve($identifier);

        if ($movie === null) {
            throw $this->notFoundFor($identifier);
        }

        $genres = $this->genreRepository->findByMovieUuid($movie->id());
        $credits = $this->creditRepository->findByMovieUuid($movie->id());

        $personUuids = array_values(array_unique(array_map(
            fn ($c) => $c->personId()->value(),
            $credits,
        )));
        $personUuidVOs = array_map(fn (string $u): Uuid => Uuid::create($u), $personUuids);
        $people = $this->personRepository->findManyByUuids($personUuidVOs);

        $videoFileId = $this->streamableFinder->findReadyVideoUuid($movie->id());

        return GetMovieDetailResponse::create($movie, $genres, $credits, $people, $videoFileId);
    }

    private function resolve(string $identifier): ?Movie
    {
        if (preg_match('/^\d+$/', $identifier) === 1) {
            $tmdbId = TmdbId::create((int) $identifier);
            $existing = $this->movieRepository->findByTmdbId($tmdbId);
            if ($existing !== null) {
                return $existing;
            }

            ($this->importMovieFromTmdb)($tmdbId->value());

            return $this->movieRepository->findByTmdbId($tmdbId);
        }

        return $this->movieRepository->findByUuid(Uuid::create($identifier));
    }

    private function notFoundFor(string $identifier): MovieNotFoundException
    {
        if (preg_match('/^\d+$/', $identifier) === 1) {
            return MovieNotFoundException::forTmdbId(TmdbId::create((int) $identifier));
        }

        return MovieNotFoundException::forUuid(Uuid::create($identifier));
    }
}
