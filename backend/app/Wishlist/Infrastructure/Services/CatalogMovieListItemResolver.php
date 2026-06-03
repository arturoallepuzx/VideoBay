<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\Services;

use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Wishlist\Domain\Interfaces\MovieListItemResolverInterface;
use App\Wishlist\Domain\ValueObject\MovieListItemView;

class CatalogMovieListItemResolver implements MovieListItemResolverInterface
{
    public function __construct(private MovieRepositoryInterface $movieRepository) {}

    public function resolveMany(array $movieIds): array
    {
        if ($movieIds === []) {
            return [];
        }

        $movies = $this->movieRepository->findManyByUuids($movieIds);

        $views = [];
        foreach ($movies as $uuid => $movie) {
            $releaseDate = $movie->releaseDate()?->value();

            $views[$uuid] = MovieListItemView::create(
                $uuid,
                $movie->title()->value(),
                $movie->posterPath()?->value(),
                $releaseDate !== null ? (int) $releaseDate->format('Y') : null,
                $movie->tmdbId()?->value(),
            );
        }

        return $views;
    }
}
