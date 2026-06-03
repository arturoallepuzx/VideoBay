<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Services;

use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Subtitle\Domain\Interfaces\SubtitleMovieSummaryResolverInterface;
use App\Subtitle\Domain\ValueObject\SubtitleMovieSummary;

class CatalogSubtitleMovieSummaryResolver implements SubtitleMovieSummaryResolverInterface
{
    public function __construct(private MovieRepositoryInterface $movieRepository) {}

    public function resolveMany(array $movieIds): array
    {
        if ($movieIds === []) {
            return [];
        }

        $movies = $this->movieRepository->findManyByUuids($movieIds);

        $summaries = [];
        foreach ($movies as $movie) {
            $releaseDate = $movie->releaseDate()?->value();
            $summaries[$movie->id()->value()] = SubtitleMovieSummary::create(
                $movie->id(),
                $movie->title()->value(),
                $movie->posterPath()?->value(),
                $releaseDate !== null ? (int) $releaseDate->format('Y') : null,
            );
        }

        return $summaries;
    }
}
