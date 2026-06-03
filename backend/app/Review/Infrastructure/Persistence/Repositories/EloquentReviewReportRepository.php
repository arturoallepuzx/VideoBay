<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Persistence\Repositories;

use App\Review\Domain\Entity\ReviewReport;
use App\Review\Domain\Interfaces\ReviewReportRepositoryInterface;
use App\Review\Domain\ValueObject\ReviewReportStatus;
use App\Review\Infrastructure\Persistence\Models\EloquentReview;
use App\Review\Infrastructure\Persistence\Models\EloquentReviewReport;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;

class EloquentReviewReportRepository implements ReviewReportRepositoryInterface
{
    public function __construct(
        private EloquentReviewReport $model,
        private UserIdResolverInterface $userIdResolver,
    ) {}

    public function findById(int $id): ?ReviewReport
    {
        $model = $this->model->newQuery()->where('id', $id)->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findByIdForUpdate(int $id): ?ReviewReport
    {
        $model = $this->model->newQuery()
            ->where('id', $id)
            ->lockForUpdate()
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findPendingByUserAndReview(Uuid $reviewId, Uuid $userId): ?ReviewReport
    {
        $internalReviewId = $this->resolveReviewInternalId($reviewId);

        if ($internalReviewId === null) {
            return null;
        }

        $model = $this->model->newQuery()
            ->where('review_id', $internalReviewId)
            ->where('reported_by_user_id', $this->userIdResolver->toInternalId($userId))
            ->where('status', ReviewReportStatus::pending()->value())
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findPendingByReviewForUpdate(Uuid $reviewId): array
    {
        $internalReviewId = $this->resolveReviewInternalId($reviewId);

        if ($internalReviewId === null) {
            return [];
        }

        return $this->model->newQuery()
            ->where('review_id', $internalReviewId)
            ->where('status', ReviewReportStatus::pending()->value())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->map(fn (EloquentReviewReport $model): ReviewReport => $this->toDomainEntity($model))
            ->all();
    }

    public function create(ReviewReport $report): ReviewReport
    {
        $internalReviewId = $this->resolveReviewInternalId($report->reviewId());

        $model = $this->model->newQuery()->create([
            'review_id' => $internalReviewId,
            'reported_by_user_id' => $this->userIdResolver->toInternalId($report->reportedByUserId()),
            'reason' => $report->reason()->value(),
            'status' => $report->status()->value(),
            'created_at' => $report->createdAt()->value(),
            'updated_at' => $report->updatedAt()->value(),
        ]);

        return ReviewReport::fromPersistence(
            (int) $model->id,
            $report->reviewId()->value(),
            $report->reportedByUserId()->value(),
            $report->reason()->value(),
            $report->status()->value(),
            $report->createdAt()->value(),
            $report->updatedAt()->value(),
        );
    }

    public function update(ReviewReport $report): void
    {
        $id = $report->id();

        if ($id === null) {
            throw new \LogicException('Cannot update a ReviewReport without persisted id.');
        }

        $this->model->newQuery()
            ->where('id', $id)
            ->update([
                'status' => $report->status()->value(),
                'updated_at' => $report->updatedAt()->value(),
            ]);
    }

    public function listPending(int $page, int $perPage): array
    {
        $base = $this->model->newQuery()->where('status', ReviewReportStatus::pending()->value());
        $total = (int) (clone $base)->count();

        $rows = $base
            ->orderBy('created_at')
            ->forPage($page, $perPage)
            ->get();

        $items = $rows->map(fn (EloquentReviewReport $m): ReviewReport => $this->toDomainEntity($m))->all();
        $totalPages = $total > 0 ? (int) ceil($total / max(1, $perPage)) : 0;

        return ['items' => $items, 'total' => $total, 'page' => $page, 'totalPages' => $totalPages];
    }

    private function resolveReviewInternalId(Uuid $reviewId): ?int
    {
        $id = EloquentReview::query()
            ->withTrashed()
            ->where('uuid', $reviewId->value())
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function toDomainEntity(EloquentReviewReport $model): ReviewReport
    {
        $reviewUuid = EloquentReview::query()
            ->withTrashed()
            ->where('id', $model->review_id)
            ->value('uuid');

        if ($reviewUuid === null) {
            throw new \LogicException(sprintf('Review "%d" for report "%d" was not found.', $model->review_id, $model->id));
        }

        $userUuid = $this->userIdResolver->toDomainUuid((int) $model->reported_by_user_id);

        return ReviewReport::fromPersistence(
            (int) $model->id,
            (string) $reviewUuid,
            $userUuid->value(),
            (string) $model->reason,
            (string) $model->status,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
