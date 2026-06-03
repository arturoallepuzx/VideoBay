<?php

declare(strict_types=1);

namespace App\Wishlist\Application\RemoveFromWatchLater;

use App\Shared\Domain\ValueObject\Uuid;
use App\Wishlist\Domain\Interfaces\WatchLaterItemRepositoryInterface;

class RemoveFromWatchLater
{
    public function __construct(
        private WatchLaterItemRepositoryInterface $repository,
    ) {}

    public function __invoke(string $userUuid, string $movieUuid): RemoveFromWatchLaterResponse
    {
        $userId = Uuid::create($userUuid);
        $movieId = Uuid::create($movieUuid);

        $wasRemoved = $this->repository->remove($userId, $movieId);

        return RemoveFromWatchLaterResponse::create($movieId, $wasRemoved);
    }
}
