<?php

declare(strict_types=1);

namespace App\Catalog\Application\GetSimilarMovies;

use App\Catalog\Application\ImportMovieFromTmdb\ImportMovieFromTmdb;
use App\Catalog\Domain\Entity\Movie;
use App\Catalog\Domain\Exception\MovieNotFoundException;
use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Catalog\Domain\Interfaces\TmdbClientInterface;
use App\Catalog\Domain\ValueObject\TmdbId;
use App\Shared\Domain\ValueObject\Uuid;

class GetSimilarMovies
{
    public function __construct(
        private TmdbClientInterface $tmdbClient,
        private MovieRepositoryInterface $movieRepository,
        private ImportMovieFromTmdb $importMovieFromTmdb,
    ) {}

    public function __invoke(string $identifier, int $page = 1): GetSimilarMoviesResponse
    {
        if ($page < 1) {
            $page = 1;
        }

        $movie = $this->resolveMovie($identifier);

        if ($movie === null || $movie->tmdbId() === null) {
            throw $this->notFoundFor($identifier);
        }

        $tmdbResult = $this->tmdbClient->getMovieRecommendations($movie->tmdbId()->value(), $page);

        return GetSimilarMoviesResponse::create($tmdbResult);
    }

    private function resolveMovie(string $identifier): ?Movie
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
