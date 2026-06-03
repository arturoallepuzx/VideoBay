<?php

declare(strict_types=1);

namespace App\Wishlist\Application\ListMyPinnedFavorites;

use App\Wishlist\Domain\Entity\PinnedFavorite;
use App\Wishlist\Domain\ValueObject\MovieListItemView;

final readonly class ListMyPinnedFavoritesResponse
{
    /** @param list<array<string, mixed>> $slots */
    private function __construct(
        public array $slots,
    ) {}

    /**
     * @param  list<PinnedFavorite>  $pins
     * @param  array<string, MovieListItemView>  $views
     */
    public static function create(array $pins, array $views): self
    {
        $slots = array_map(
            fn (PinnedFavorite $pin): array => self::pinToArray($pin, $views),
            $pins,
        );

        return new self(array_values($slots));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'slots' => $this->slots,
        ];
    }

    /**
     * @param  array<string, MovieListItemView>  $views
     * @return array<string, mixed>
     */
    private static function pinToArray(PinnedFavorite $pin, array $views): array
    {
        $view = $views[$pin->movieId()->value()] ?? null;

        return [
            'position' => $pin->position(),
            'movie_id' => $pin->movieId()->value(),
            'title' => $view?->title(),
            'poster_path' => $view?->posterPath(),
            'release_year' => $view?->releaseYear(),
        ];
    }
}
