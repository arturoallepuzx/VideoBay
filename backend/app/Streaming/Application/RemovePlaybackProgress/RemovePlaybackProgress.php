<?php

declare(strict_types=1);

namespace App\Streaming\Application\RemovePlaybackProgress;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Interfaces\PlaybackProgressRepositoryInterface;

class RemovePlaybackProgress
{
    public function __construct(
        private PlaybackProgressRepositoryInterface $progressRepository,
    ) {}

    public function __invoke(string $userUuid, string $movieUuid): void
    {
        $this->progressRepository->delete(
            Uuid::create($userUuid),
            Uuid::create($movieUuid),
        );
    }
}
