<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\MovieIdResolverInterface;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;
use App\Streaming\Domain\Entity\PlaybackProgress;
use App\Streaming\Domain\Interfaces\PlaybackProgressRepositoryInterface;
use Illuminate\Database\ConnectionInterface;

class EloquentPlaybackProgressRepository implements PlaybackProgressRepositoryInterface
{
    private const TABLE = 'playback_progress';

    public function __construct(
        private ConnectionInterface $connection,
        private UserIdResolverInterface $userIdResolver,
        private MovieIdResolverInterface $movieIdResolver,
    ) {}

    public function find(Uuid $userId, Uuid $movieId): ?PlaybackProgress
    {
        $row = $this->connection->table(self::TABLE)
            ->where('user_id', $this->userIdResolver->toInternalId($userId))
            ->where('movie_id', $this->movieIdResolver->toInternalId($movieId))
            ->first();

        return $row !== null ? $this->toDomainEntity($row, $userId->value(), $movieId->value()) : null;
    }

    public function save(PlaybackProgress $progress): void
    {
        $this->connection->table(self::TABLE)->updateOrInsert(
            [
                'user_id' => $this->userIdResolver->toInternalId($progress->userId()),
                'movie_id' => $this->movieIdResolver->toInternalId($progress->movieId()),
            ],
            [
                'position_seconds' => $progress->positionSeconds(),
                'duration_seconds' => $progress->durationSeconds(),
                'completed' => $progress->completed(),
                'updated_at' => $progress->updatedAt()->value(),
            ],
        );
    }

    public function delete(Uuid $userId, Uuid $movieId): void
    {
        $this->connection->table(self::TABLE)
            ->where('user_id', $this->userIdResolver->toInternalId($userId))
            ->where('movie_id', $this->movieIdResolver->toInternalId($movieId))
            ->delete();
    }

    public function listContinueWatchingByUser(Uuid $userId, int $page, int $perPage): array
    {
        return $this->paginateByUser($userId, false, $page, $perPage);
    }

    public function listCompletedHistoryByUser(Uuid $userId, int $page, int $perPage): array
    {
        return $this->paginateByUser($userId, true, $page, $perPage);
    }

    /**
     * @return array{items: list<PlaybackProgress>, total: int, page: int, totalPages: int}
     */
    private function paginateByUser(Uuid $userId, bool $completed, int $page, int $perPage): array
    {
        $internalUserId = $this->userIdResolver->toInternalId($userId);

        $base = $this->connection->table(self::TABLE.' as p')
            ->join('movies as m', 'p.movie_id', '=', 'm.id')
            ->where('p.user_id', $internalUserId)
            ->where('p.completed', $completed)
            ->whereNull('m.deleted_at');

        $total = (int) (clone $base)->count();

        $rows = $base
            ->orderByDesc('p.updated_at')
            ->forPage($page, $perPage)
            ->get(['p.position_seconds', 'p.duration_seconds', 'p.completed', 'p.updated_at', 'm.uuid as movie_uuid']);

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->toDomainEntity($row, $userId->value(), (string) $row->movie_uuid);
        }

        $totalPages = $total > 0 ? (int) ceil($total / max(1, $perPage)) : 0;

        return ['items' => $items, 'total' => $total, 'page' => $page, 'totalPages' => $totalPages];
    }

    private function toDomainEntity(object $row, string $userUuid, string $movieUuid): PlaybackProgress
    {
        return PlaybackProgress::fromPersistence(
            $userUuid,
            $movieUuid,
            (int) $row->position_seconds,
            $row->duration_seconds !== null ? (int) $row->duration_seconds : null,
            (bool) $row->completed,
            new \DateTimeImmutable((string) $row->updated_at),
        );
    }
}
