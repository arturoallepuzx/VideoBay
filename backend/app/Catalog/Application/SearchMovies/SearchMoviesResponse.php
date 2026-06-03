<?php

declare(strict_types=1);

namespace App\Catalog\Application\SearchMovies;

use App\Catalog\Domain\Entity\Movie;

final readonly class SearchMoviesResponse
{
    /** @param list<array<string, mixed>> $results */
    private function __construct(
        public array $results,
        public int $page,
        public int $totalPages,
        public int $totalResults,
    ) {}

    public static function empty(int $page): self
    {
        return new self([], $page, 0, 0);
    }

    /**
     * @param  array{results: list<array<string, mixed>>, page: int, total_pages: int, total_results: int}  $tmdbResult
     */
    public static function fromTmdb(array $tmdbResult): self
    {
        $results = array_map(
            fn (array $movie): array => [
                'tmdb_id' => isset($movie['id']) ? (int) $movie['id'] : null,
                'title' => isset($movie['title']) ? (string) $movie['title'] : '',
                'original_title' => isset($movie['original_title']) ? (string) $movie['original_title'] : null,
                'overview' => isset($movie['overview']) && $movie['overview'] !== '' ? (string) $movie['overview'] : null,
                'release_date' => isset($movie['release_date']) && $movie['release_date'] !== '' ? (string) $movie['release_date'] : null,
                'poster_path' => isset($movie['poster_path']) && $movie['poster_path'] !== '' ? (string) $movie['poster_path'] : null,
                'backdrop_path' => isset($movie['backdrop_path']) && $movie['backdrop_path'] !== '' ? (string) $movie['backdrop_path'] : null,
                'tmdb_rating' => isset($movie['vote_average']) ? (float) $movie['vote_average'] : null,
                'original_language' => isset($movie['original_language']) ? (string) $movie['original_language'] : null,
            ],
            $tmdbResult['results'],
        );

        return new self(
            results: array_values($results),
            page: $tmdbResult['page'],
            totalPages: $tmdbResult['total_pages'],
            totalResults: $tmdbResult['total_results'],
        );
    }

    /**
     * @param  array{movies: list<Movie>, total: int, page: int, totalPages: int}  $fallback
     */
    public static function fromLocalFallback(array $fallback): self
    {
        $results = array_map(
            fn (Movie $movie): array => [
                'tmdb_id' => $movie->tmdbId()?->value(),
                'title' => $movie->title()->value(),
                'original_title' => $movie->originalTitle()?->value(),
                'overview' => $movie->overview()?->value(),
                'release_date' => $movie->releaseDate()?->format('Y-m-d'),
                'poster_path' => $movie->posterPath()?->value(),
                'backdrop_path' => $movie->backdropPath()?->value(),
                'tmdb_rating' => $movie->tmdbRating()?->value(),
                'original_language' => $movie->originalLanguage()?->value(),
            ],
            $fallback['movies'],
        );

        return new self(
            results: array_values($results),
            page: $fallback['page'],
            totalPages: $fallback['totalPages'],
            totalResults: $fallback['total'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'results' => $this->results,
            'page' => $this->page,
            'total_pages' => $this->totalPages,
            'total_results' => $this->totalResults,
        ];
    }
}
