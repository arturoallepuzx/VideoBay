<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Persistence\Repositories;

use App\Review\Domain\Entity\Review;
use App\Review\Domain\Exception\ReviewAlreadyExistsException;
use App\Review\Domain\Interfaces\ReviewRepositoryInterface;
use App\Review\Infrastructure\Persistence\Models\EloquentReview;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\MovieIdResolverInterface;
use App\Shared\Infrastructure\Persistence\MysqlUniqueConstraintViolationDetector;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;

class EloquentReviewRepository implements ReviewRepositoryInterface
{
    private const UNIQUE_USER_MOVIE_CONSTRAINT = 'reviews_user_movie_active_unique';

    public function __construct(
        private EloquentReview $model,
        private UserIdResolverInterface $userIdResolver,
        private MovieIdResolverInterface $movieIdResolver,
        private MysqlUniqueConstraintViolationDetector $uniqueConstraintViolationDetector,
    ) {}

    public function findByUuid(Uuid $uuid): ?Review
    {
        $model = $this->model->newQuery()
            ->where('uuid', $uuid->value())
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findByUuidForUpdate(Uuid $uuid): ?Review
    {
        $model = $this->model->newQuery()
            ->where('uuid', $uuid->value())
            ->lockForUpdate()
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findByUserAndMovie(Uuid $userId, Uuid $movieId): ?Review
    {
        $model = $this->model->newQuery()
            ->where('user_id', $this->userIdResolver->toInternalId($userId))
            ->where('movie_id', $this->movieIdResolver->toInternalId($movieId))
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findManyByUuids(array $uuids): array
    {
        if ($uuids === []) {
            return [];
        }

        $values = array_map(fn (Uuid $uuid): string => $uuid->value(), $uuids);

        return $this->model->newQueryWithoutScopes()
            ->whereIn('uuid', $values)
            ->get()
            ->map(fn (EloquentReview $m): Review => $this->toDomainEntity($m))
            ->all();
    }

    public function create(Review $review): void
    {
        try {
            $this->model->newQuery()->create([
                'uuid' => $review->id()->value(),
                'user_id' => $this->userIdResolver->toInternalId($review->userId()),
                'movie_id' => $this->movieIdResolver->toInternalId($review->movieId()),
                'rating' => $review->rating()->value(),
                'body' => $review->body()?->value(),
                'contains_spoilers' => $review->containsSpoilers(),
                'likes_count' => $review->likesCount(),
                'created_at' => $review->createdAt()->value(),
                'updated_at' => $review->updatedAt()->value(),
            ]);
        } catch (QueryException $e) {
            if ($this->uniqueConstraintViolationDetector->matches($e, self::UNIQUE_USER_MOVIE_CONSTRAINT)) {
                throw ReviewAlreadyExistsException::forUserAndMovie($review->userId(), $review->movieId());
            }

            throw $e;
        }
    }

    public function update(Review $review): void
    {
        $this->model->newQuery()
            ->where('uuid', $review->id()->value())
            ->update([
                'rating' => $review->rating()->value(),
                'body' => $review->body()?->value(),
                'contains_spoilers' => $review->containsSpoilers(),
                'likes_count' => $review->likesCount(),
                'updated_at' => $review->updatedAt()->value(),
                'deleted_at' => $review->deletedAt()?->value(),
            ]);
    }

    public function listByMovie(Uuid $movieId, int $page, int $perPage): array
    {
        return $this->paginate(
            $this->model->newQuery()->where('movie_id', $this->movieIdResolver->toInternalId($movieId)),
            $page,
            $perPage,
        );
    }

    public function listByUser(Uuid $userId, int $page, int $perPage): array
    {
        return $this->paginate(
            $this->model->newQuery()->where('user_id', $this->userIdResolver->toInternalId($userId)),
            $page,
            $perPage,
        );
    }

    private function paginate(Builder $base, int $page, int $perPage): array
    {
        $total = (int) (clone $base)->count();

        $rows = $base
            ->orderByDesc('created_at')
            ->forPage($page, $perPage)
            ->get();

        $items = $rows->map(fn (EloquentReview $m): Review => $this->toDomainEntity($m))->all();
        $totalPages = $total > 0 ? (int) ceil($total / max(1, $perPage)) : 0;

        return ['items' => $items, 'total' => $total, 'page' => $page, 'totalPages' => $totalPages];
    }

    private function toDomainEntity(EloquentReview $model): Review
    {
        $userUuid = $this->userIdResolver->toDomainUuid((int) $model->user_id);
        $movieUuid = $this->movieIdResolver->toDomainUuid((int) $model->movie_id);

        return Review::fromPersistence(
            (string) $model->uuid,
            $userUuid->value(),
            $movieUuid->value(),
            (int) $model->rating,
            $model->body !== null ? (string) $model->body : null,
            (bool) $model->contains_spoilers,
            (int) $model->likes_count,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            $model->deleted_at?->toDateTimeImmutable(),
        );
    }
}
