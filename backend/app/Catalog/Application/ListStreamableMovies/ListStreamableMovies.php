<?php

declare(strict_types=1);

namespace App\Catalog\Application\ListStreamableMovies;

use App\Catalog\Domain\Interfaces\StreamableMovieFinderInterface;
use App\Catalog\Domain\ValueObject\MovieCatalogCriteria;

class ListStreamableMovies
{
    public function __construct(
        private StreamableMovieFinderInterface $finder,
    ) {}

    public function __invoke(
        ?string $genre,
        ?int $yearFrom,
        ?int $yearTo,
        ?string $sort,
        int $page,
        int $perPage,
    ): ListStreamableMoviesResponse {
        $criteria = MovieCatalogCriteria::create($genre, $yearFrom, $yearTo, $sort, $page, $perPage);

        return ListStreamableMoviesResponse::create($this->finder->find($criteria));
    }
}
