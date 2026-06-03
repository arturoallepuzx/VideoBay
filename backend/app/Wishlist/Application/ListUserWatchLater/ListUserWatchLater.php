<?php

declare(strict_types=1);

namespace App\Wishlist\Application\ListUserWatchLater;

use App\Shared\Domain\ValueObject\Uuid;
use App\Wishlist\Domain\Entity\WatchLaterItem;
use App\Wishlist\Domain\Interfaces\MovieListItemResolverInterface;
use App\Wishlist\Domain\Interfaces\WatchLaterItemRepositoryInterface;

class ListUserWatchLater
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private WatchLaterItemRepositoryInterface $repository,
        private MovieListItemResolverInterface $movieListItemResolver,
    ) {}

    public function __invoke(string $userUuid, int $page, int $perPage): ListUserWatchLaterResponse
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE));

        $result = $this->repository->listByUser(Uuid::create($userUuid), $page, $perPage);

        $movieIds = array_map(fn (WatchLaterItem $item): Uuid => $item->movieId(), $result['items']);
        $views = $this->movieListItemResolver->resolveMany($movieIds);

        return ListUserWatchLaterResponse::create($result, $views);
    }
}
