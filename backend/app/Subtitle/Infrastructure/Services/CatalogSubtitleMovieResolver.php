<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Services;

use App\Catalog\Domain\Exception\MovieNotFoundException;
use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\Interfaces\SubtitleMovieResolverInterface;
use App\Subtitle\Domain\ValueObject\SubtitleMovieMetadata;

class CatalogSubtitleMovieResolver implements SubtitleMovieResolverInterface
{
    public function __construct(private MovieRepositoryInterface $movieRepository) {}

    public function resolve(Uuid $movieId): SubtitleMovieMetadata
    {
        $movie = $this->movieRepository->findByUuid($movieId);

        if ($movie === null) {
            throw MovieNotFoundException::forUuid($movieId);
        }

        $releaseDate = $movie->releaseDate()?->value();

        return SubtitleMovieMetadata::create(
            $movie->id(),
            $movie->title()->value(),
            $movie->tmdbId()?->value(),
            $movie->imdbId()?->value(),
            $releaseDate !== null ? (int) $releaseDate->format('Y') : null,
        );
    }
}
