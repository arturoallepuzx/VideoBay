<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Persistence\Repositories;

use App\Review\Domain\Entity\ReviewLike;
use App\Review\Domain\Interfaces\ReviewLikeRepositoryInterface;
use App\Review\Infrastructure\Persistence\Models\EloquentReview;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;
use Illuminate\Database\ConnectionInterface;

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
