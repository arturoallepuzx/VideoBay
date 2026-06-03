<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Services;

use App\Catalog\Application\ImportMovieFromTmdb\ImportMovieFromTmdb;
use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Catalog\Domain\ValueObject\TmdbId;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Interfaces\MovieResolverForStreamingInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CatalogMovieResolverForStreaming implements MovieResolverForStreamingInterface
{
    public function __construct(
        private MovieRepositoryInterface $movieRepository,
        private ImportMovieFromTmdb $importMovieFromTmdb,
    ) {}

    public function resolveByUuid(Uuid $uuid): Uuid
    {
        $movie = $this->movieRepository->findByUuid($uuid);

        if ($movie === null) {
            throw (new ModelNotFoundException)->setModel('movies', [$uuid->value()]);
        }

        return $movie->id();
    }

    public function resolveByTmdbId(int $tmdbId): Uuid
    {
        $tmdb = TmdbId::create($tmdbId);
        $existing = $this->movieRepository->findByTmdbId($tmdb);

        if ($existing !== null) {
            return $existing->id();
        }

        ($this->importMovieFromTmdb)($tmdbId);

        return $this->movieRepository->findByTmdbId($tmdb)->id();
    }
}
