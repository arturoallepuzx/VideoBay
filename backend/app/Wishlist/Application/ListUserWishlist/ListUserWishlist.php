<?php

declare(strict_types=1);

namespace App\Wishlist\Application\ListUserWishlist;

use App\Shared\Domain\ValueObject\Uuid;
use App\Wishlist\Domain\Entity\WishlistItem;
use App\Wishlist\Domain\Interfaces\MovieListItemResolverInterface;
use App\Wishlist\Domain\Interfaces\WishlistItemRepositoryInterface;

class ListUserWishlist
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private WishlistItemRepositoryInterface $repository,
        private MovieListItemResolverInterface $movieListItemResolver,
    ) {}

    public function __invoke(string $userUuid, int $page, int $perPage): ListUserWishlistResponse
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE));

        $result = $this->repository->listByUser(Uuid::create($userUuid), $page, $perPage);

        $movieIds = array_map(fn (WishlistItem $item): Uuid => $item->movieId(), $result['items']);
        $views = $this->movieListItemResolver->resolveMany($movieIds);

        return ListUserWishlistResponse::create($result, $views);
    }
}
