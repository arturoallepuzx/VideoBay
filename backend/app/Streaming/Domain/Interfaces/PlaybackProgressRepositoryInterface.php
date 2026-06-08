<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Entity\PlaybackProgress;

interface PlaybackProgressRepositoryInterface
{
    public function find(Uuid $userId, Uuid $movieId): ?PlaybackProgress;

    public function save(PlaybackProgress $progress): void;

    public function delete(Uuid $userId, Uuid $movieId): void;

    /** @return array{items: list<PlaybackProgress>, total: int, page: int, totalPages: int} */
    public function listContinueWatchingByUser(Uuid $userId, int $page, int $perPage): array;

    /** @return array{items: list<PlaybackProgress>, total: int, page: int, totalPages: int} */
    public function listCompletedHistoryByUser(Uuid $userId, int $page, int $perPage): array;
}
