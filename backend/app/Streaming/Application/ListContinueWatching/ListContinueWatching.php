<?php

declare(strict_types=1);

namespace App\Streaming\Application\ListContinueWatching;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Interfaces\PlaybackProgressRepositoryInterface;

class ListContinueWatching
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private PlaybackProgressRepositoryInterface $progressRepository,
    ) {}

    public function __invoke(string $userUuid, int $page, int $perPage): ListContinueWatchingResponse
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE));

        $result = $this->progressRepository->listContinueWatchingByUser(Uuid::create($userUuid), $page, $perPage);

        return ListContinueWatchingResponse::create($result);
    }
}
