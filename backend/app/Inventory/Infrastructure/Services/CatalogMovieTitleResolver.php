<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Services;

use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Inventory\Domain\Interfaces\MovieTitleResolverInterface;

class CatalogMovieTitleResolver implements MovieTitleResolverInterface
{
    public function __construct(private MovieRepositoryInterface $movieRepository) {}

    public function resolveTitles(array $movieIds): array
    {
        if ($movieIds === []) {
            return [];
        }

        $movies = $this->movieRepository->findManyByUuids($movieIds);

        $titles = [];
        foreach ($movies as $uuid => $movie) {
            $titles[$uuid] = $movie->title()->value();
        }

        return $titles;
    }
}
