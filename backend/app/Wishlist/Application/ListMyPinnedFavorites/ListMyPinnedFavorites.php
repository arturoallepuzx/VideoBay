<?php

declare(strict_types=1);

namespace App\Wishlist\Application\ListMyPinnedFavorites;

use App\Shared\Domain\ValueObject\Uuid;
use App\Wishlist\Domain\Entity\PinnedFavorite;
use App\Wishlist\Domain\Interfaces\MovieListItemResolverInterface;
use App\Wishlist\Domain\Interfaces\PinnedFavoriteRepositoryInterface;

class ListMyPinnedFavorites
{
    public function __construct(
        private PinnedFavoriteRepositoryInterface $repository,
        private MovieListItemResolverInterface $movieListItemResolver,
    ) {}

    public function __invoke(string $userUuid): ListMyPinnedFavoritesResponse
    {
        $pins = $this->repository->listByUser(Uuid::create($userUuid));

        $movieIds = array_map(fn (PinnedFavorite $pin): Uuid => $pin->movieId(), $pins);
        $views = $this->movieListItemResolver->resolveMany($movieIds);

        return ListMyPinnedFavoritesResponse::create($pins, $views);
    }
}
