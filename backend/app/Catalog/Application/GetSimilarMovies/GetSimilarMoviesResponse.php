<?php

declare(strict_types=1);

namespace App\Catalog\Application\GetSimilarMovies;

final readonly class GetSimilarMoviesResponse
{
    /** @param list<array<string, mixed>> $results */
    private function __construct(
        public array $results,
        public int $page,
        public int $totalPages,
        public int $totalResults,
    ) {}

    /**
     * @param  array{results: list<array<string, mixed>>, page: int, total_pages: int, total_results: int}  $tmdbResult
     */
    public static function create(array $tmdbResult): self
    {
        $results = array_map(
            fn (array $movie): array => [
                'tmdb_id' => isset($movie['id']) ? (int) $movie['id'] : null,
                'title' => isset($movie['title']) ? (string) $movie['title'] : '',
                'overview' => isset($movie['overview']) && $movie['overview'] !== '' ? (string) $movie['overview'] : null,
                'release_date' => isset($movie['release_date']) && $movie['release_date'] !== '' ? (string) $movie['release_date'] : null,
                'poster_path' => isset($movie['poster_path']) && $movie['poster_path'] !== '' ? (string) $movie['poster_path'] : null,
                'backdrop_path' => isset($movie['backdrop_path']) && $movie['backdrop_path'] !== '' ? (string) $movie['backdrop_path'] : null,
                'tmdb_rating' => isset($movie['vote_average']) ? (float) $movie['vote_average'] : null,
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
