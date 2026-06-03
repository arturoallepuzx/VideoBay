<?php

declare(strict_types=1);

namespace App\Streaming\Application\GetPlaybackProgress;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Interfaces\PlaybackProgressRepositoryInterface;

class GetPlaybackProgress
{
    public function __construct(
        private PlaybackProgressRepositoryInterface $progressRepository,
    ) {}

    public function __invoke(string $userUuid, string $movieUuid): GetPlaybackProgressResponse
    {
        $progress = $this->progressRepository->find(
            Uuid::create($userUuid),
            Uuid::create($movieUuid),
        );

        return GetPlaybackProgressResponse::create($progress);
    }
}
