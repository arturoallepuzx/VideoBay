<?php

declare(strict_types=1);

namespace App\Wishlist\Application\RemoveFromWishlist;

use App\Shared\Domain\ValueObject\Uuid;
use App\Wishlist\Domain\Interfaces\WishlistItemRepositoryInterface;

class RemoveFromWishlist
{
    public function __construct(
        private WishlistItemRepositoryInterface $repository,
    ) {}

    public function __invoke(string $userUuid, string $movieUuid): RemoveFromWishlistResponse
    {
        $userId = Uuid::create($userUuid);
        $movieId = Uuid::create($movieUuid);

        $wasRemoved = $this->repository->remove($userId, $movieId);

        return RemoveFromWishlistResponse::create($movieId, $wasRemoved);
    }
}
