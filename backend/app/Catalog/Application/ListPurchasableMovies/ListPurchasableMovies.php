<?php

declare(strict_types=1);

namespace App\Catalog\Application\ListPurchasableMovies;

use App\Catalog\Domain\Interfaces\PurchasableMovieFinderInterface;
use App\Catalog\Domain\ValueObject\MovieCatalogCriteria;

class ListPurchasableMovies
{
    public function __construct(
        private PurchasableMovieFinderInterface $finder,
    ) {}

    public function __invoke(
        ?string $genre,
        ?int $yearFrom,
        ?int $yearTo,
        ?string $sort,
        int $page,
        int $perPage,
    ): ListPurchasableMoviesResponse {
        $criteria = MovieCatalogCriteria::create($genre, $yearFrom, $yearTo, $sort, $page, $perPage);

        return ListPurchasableMoviesResponse::create($this->finder->find($criteria));
    }
}
