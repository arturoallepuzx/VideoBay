<?php

declare(strict_types=1);

namespace App\Catalog\Application\SearchMovies;

use App\Catalog\Domain\Exception\TmdbServiceUnavailableException;
use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Catalog\Domain\Interfaces\TmdbClientInterface;

class SearchMovies
{
    private const FALLBACK_PER_PAGE = 20;

    public function __construct(
        private TmdbClientInterface $tmdbClient,
        private MovieRepositoryInterface $movieRepository,
    ) {}

    public function __invoke(string $query, int $page = 1): SearchMoviesResponse
    {
        $trimmed = trim($query);

        if ($trimmed === '') {
            return SearchMoviesResponse::empty($page);
        }

        if ($page < 1) {
            $page = 1;
        }

        try {
            $tmdbResult = $this->tmdbClient->searchMovies($trimmed, $page);

            return SearchMoviesResponse::fromTmdb($tmdbResult);
        } catch (TmdbServiceUnavailableException) {
            $fallback = $this->movieRepository->searchByFulltext($trimmed, $page, self::FALLBACK_PER_PAGE);

            return SearchMoviesResponse::fromLocalFallback($fallback);
        }
    }
}
