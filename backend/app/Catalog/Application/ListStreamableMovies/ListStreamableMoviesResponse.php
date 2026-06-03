<?php

declare(strict_types=1);

namespace App\Catalog\Application\ListStreamableMovies;

use App\Catalog\Domain\ValueObject\MovieCardView;

final readonly class ListStreamableMoviesResponse
{
    /** @param list<array<string, mixed>> $items */
    private function __construct(
        public array $items,
        public int $page,
        public int $totalPages,
        public int $total,
    ) {}

    /** @param array{items: list<MovieCardView>, total: int, page: int, totalPages: int} $result */
    public static function create(array $result): self
    {
        $items = array_map(
            fn (MovieCardView $card): array => self::cardToArray($card),
            $result['items'],
        );

        return new self(
            items: array_values($items),
            page: $result['page'],
            totalPages: $result['totalPages'],
            total: $result['total'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'page' => $this->page,
            'total_pages' => $this->totalPages,
            'total' => $this->total,
        ];
    }

    /** @return array<string, mixed> */
    private static function cardToArray(MovieCardView $card): array
    {
        return [
            'movie_id' => $card->uuid(),
            'title' => $card->title(),
            'poster_path' => $card->posterPath(),
            'release_year' => $card->releaseYear(),
            'tmdb_rating' => $card->tmdbRating(),
        ];
    }
}
