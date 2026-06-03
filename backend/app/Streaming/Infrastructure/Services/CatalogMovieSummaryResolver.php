<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Services;

use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Streaming\Domain\Interfaces\MovieSummaryResolverInterface;
use App\Streaming\Domain\ValueObject\MovieSummary;

class CatalogMovieSummaryResolver implements MovieSummaryResolverInterface
{
    public function __construct(
        private MovieRepositoryInterface $movieRepository,
    ) {}

    public function resolveMany(array $movieIds): array
    {
        if ($movieIds === []) {
            return [];
        }

        $movies = $this->movieRepository->findManyByUuids($movieIds);

        $summaries = [];
        foreach ($movies as $movie) {
            $releaseDate = $movie->releaseDate()?->value();

            $summaries[$movie->id()->value()] = MovieSummary::create(
                $movie->id(),
                $movie->title()->value(),
                $movie->posterPath()?->value(),
                $releaseDate !== null ? (int) $releaseDate->format('Y') : null,
            );
        }

        return $summaries;
    }
}
