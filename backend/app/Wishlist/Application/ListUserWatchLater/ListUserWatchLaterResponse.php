<?php

declare(strict_types=1);

namespace App\Wishlist\Application\ListUserWatchLater;

use App\Wishlist\Domain\Entity\WatchLaterItem;
use App\Wishlist\Domain\ValueObject\MovieListItemView;

final readonly class ListUserWatchLaterResponse
{
    /** @param list<array<string, mixed>> $items */
    private function __construct(
        public array $items,
        public int $page,
        public int $totalPages,
        public int $total,
    ) {}

    /**
     * @param  array{items: list<WatchLaterItem>, total: int, page: int, totalPages: int}  $result
     * @param  array<string, MovieListItemView>  $views
     */
    public static function create(array $result, array $views): self
    {
        $items = array_map(
            fn (WatchLaterItem $item): array => self::itemToArray($item, $views),
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

    /**
     * @param  array<string, MovieListItemView>  $views
     * @return array<string, mixed>
     */
    private static function itemToArray(WatchLaterItem $item, array $views): array
    {
        $view = $views[$item->movieId()->value()] ?? null;

        return [
            'movie_id' => $item->movieId()->value(),
            'tmdb_id' => $view?->tmdbId(),
            'title' => $view?->title(),
            'poster_path' => $view?->posterPath(),
            'release_year' => $view?->releaseYear(),
            'added_at' => $item->addedAt()->value()->format(\DateTimeInterface::ATOM),
        ];
    }
}
