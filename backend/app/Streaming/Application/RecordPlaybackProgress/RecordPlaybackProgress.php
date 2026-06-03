<?php

declare(strict_types=1);

namespace App\Streaming\Application\RecordPlaybackProgress;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Entity\PlaybackProgress;
use App\Streaming\Domain\Interfaces\PlaybackProgressRepositoryInterface;

class RecordPlaybackProgress
{
    public function __construct(
        private PlaybackProgressRepositoryInterface $progressRepository,
    ) {}

    public function __invoke(
        string $userUuid,
        string $movieUuid,
        int $positionSeconds,
        ?int $durationSeconds,
        bool $completed,
    ): void {
        $userId = Uuid::create($userUuid);
        $movieId = Uuid::create($movieUuid);

        $progress = $this->progressRepository->find($userId, $movieId);

        if ($progress === null) {
            $progress = PlaybackProgress::dddCreate($userId, $movieId, $positionSeconds, $durationSeconds, $completed);
            $this->progressRepository->save($progress);

            return;
        }

        $progress->reportPosition($positionSeconds, $durationSeconds, $completed);

        if ($progress->wasModified()) {
            $this->progressRepository->save($progress);
        }
    }
}
