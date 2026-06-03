<?php

declare(strict_types=1);

namespace App\Wishlist\Application\AddToWishlist;

use App\Shared\Domain\ValueObject\Uuid;
use App\Wishlist\Domain\Entity\WishlistItem;
use App\Wishlist\Domain\Interfaces\WishlistItemRepositoryInterface;

class AddToWishlist
{
    public function __construct(
        private WishlistItemRepositoryInterface $repository,
    ) {}

    public function __invoke(string $userUuid, string $movieUuid): AddToWishlistResponse
    {
        $userId = Uuid::create($userUuid);
        $movieId = Uuid::create($movieUuid);

        $item = WishlistItem::dddCreate($userId, $movieId);
        $wasNew = $this->repository->add($item);

        return AddToWishlistResponse::create($movieId, $wasNew);
    }
}
