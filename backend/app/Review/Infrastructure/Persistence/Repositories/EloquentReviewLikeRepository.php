<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Persistence\Repositories;

use App\Review\Domain\Entity\ReviewLike;
use App\Review\Domain\Interfaces\ReviewLikeRepositoryInterface;
use App\Review\Infrastructure\Persistence\Models\EloquentReview;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;

class EloquentReviewLikeRepository implements ReviewLikeRepositoryInterface
{
    private const TABLE = 'review_likes';

    public function __construct(
        private ConnectionInterface $connection,
        private UserIdResolverInterface $userIdResolver,
    ) {}

    public function exists(Uuid $reviewId, Uuid $userId): bool
    {
        $internalReviewId = $this->resolveReviewInternalId($reviewId);

        if ($internalReviewId === null) {
            return false;
        }

        return $this->connection->table(self::TABLE)
            ->where('review_id', $internalReviewId)
            ->where('user_id', $this->userIdResolver->toInternalId($userId))
            ->exists();
    }

    public function likedReviewIds(array $reviewIds, Uuid $userId): array
    {
        if ($reviewIds === []) {
            return [];
        }

        $uuidValues = array_map(fn (Uuid $id): string => $id->value(), $reviewIds);

        /** @var Collection<int, string> $idMap */
        $idMap = EloquentReview::query()
            ->whereIn('uuid', $uuidValues)
            ->pluck('uuid', 'id');

        if ($idMap->isEmpty()) {
            return [];
        }

        return $this->connection->table(self::TABLE)
            ->where('user_id', $this->userIdResolver->toInternalId($userId))
            ->whereIn('review_id', $idMap->keys()->all())
            ->pluck('review_id')
            ->map(fn ($internalId): string => (string) $idMap[(int) $internalId])
            ->values()
            ->all();
    }

    public function add(ReviewLike $like): void
    {
        $internalReviewId = $this->resolveReviewInternalId($like->reviewId());

        if ($internalReviewId === null) {
            return;
        }

        $this->connection->table(self::TABLE)->insertOrIgnore([
            'review_id' => $internalReviewId,
            'user_id' => $this->userIdResolver->toInternalId($like->userId()),
            'created_at' => $like->createdAt()->value(),
        ]);
    }

    public function remove(Uuid $reviewId, Uuid $userId): bool
    {
        $internalReviewId = $this->resolveReviewInternalId($reviewId);

        if ($internalReviewId === null) {
            return false;
        }

        $deleted = $this->connection->table(self::TABLE)
            ->where('review_id', $internalReviewId)
            ->where('user_id', $this->userIdResolver->toInternalId($userId))
            ->delete();

        return $deleted > 0;
    }

    private function resolveReviewInternalId(Uuid $reviewId): ?int
    {
        $id = EloquentReview::query()
            ->where('uuid', $reviewId->value())
            ->value('id');

        return $id !== null ? (int) $id : null;
    }
}
