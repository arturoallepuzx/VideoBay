<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Services;

use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Inventory\Domain\Interfaces\MovieSummaryResolverInterface;
use App\Inventory\Domain\ValueObject\MovieSummary;

class CatalogMovieSummaryResolver implements MovieSummaryResolverInterface
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
            $summaries[$movie->id()->value()] = MovieSummary::create(
                $movie->id(),
                $movie->title()->value(),
                $movie->posterPath()?->value(),
            );
        }

        return $summaries;
    }
}
