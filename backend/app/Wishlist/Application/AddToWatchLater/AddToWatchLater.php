<?php

declare(strict_types=1);

namespace App\Wishlist\Application\AddToWatchLater;

use App\Shared\Domain\ValueObject\Uuid;
use App\Wishlist\Domain\Entity\WatchLaterItem;
use App\Wishlist\Domain\Interfaces\WatchLaterItemRepositoryInterface;

class AddToWatchLater
{
    public function __construct(
        private WatchLaterItemRepositoryInterface $repository,
    ) {}

    public function __invoke(string $userUuid, string $movieUuid): AddToWatchLaterResponse
    {
        $userId = Uuid::create($userUuid);
        $movieId = Uuid::create($movieUuid);

        $item = WatchLaterItem::dddCreate($userId, $movieId);
        $wasNew = $this->repository->add($item);

        return AddToWatchLaterResponse::create($movieId, $wasNew);
    }
}
