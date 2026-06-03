<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;
use App\Subtitle\Domain\Entity\SubtitleReport;
use App\Subtitle\Domain\Interfaces\SubtitleReportRepositoryInterface;
use App\Subtitle\Domain\ValueObject\SubtitleReportStatus;
use App\Subtitle\Infrastructure\Persistence\Models\EloquentSubtitle;
use App\Subtitle\Infrastructure\Persistence\Models\EloquentSubtitleReport;

class EloquentSubtitleReportRepository implements SubtitleReportRepositoryInterface
{
    public function __construct(
        private EloquentSubtitleReport $model,
        private UserIdResolverInterface $userIdResolver,
    ) {}

    public function findByIdForUpdate(int $id): ?SubtitleReport
    {
        $model = $this->model->newQuery()
            ->where('id', $id)
            ->lockForUpdate()
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findPendingByUserAndSubtitle(Uuid $subtitleId, Uuid $userId): ?SubtitleReport
    {
        $internalSubtitleId = $this->resolveSubtitleInternalId($subtitleId);

        if ($internalSubtitleId === null) {
            return null;
        }

        $model = $this->model->newQuery()
            ->where('subtitle_id', $internalSubtitleId)
            ->where('reported_by_user_id', $this->userIdResolver->toInternalId($userId))
            ->where('status', SubtitleReportStatus::pending()->value())
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function create(SubtitleReport $report): SubtitleReport
    {
        $model = $this->model->newQuery()->create([
            'subtitle_id' => $this->resolveSubtitleInternalId($report->subtitleId()),
            'reported_by_user_id' => $this->userIdResolver->toInternalId($report->reportedByUserId()),
            'reason' => $report->reason()->value(),
            'status' => $report->status()->value(),
            'created_at' => $report->createdAt()->value(),
            'updated_at' => $report->updatedAt()->value(),
        ]);

        return SubtitleReport::fromPersistence(
            (int) $model->id,
            $report->subtitleId()->value(),
            $report->reportedByUserId()->value(),
            $report->reason()->value(),
            $report->status()->value(),
            $report->createdAt()->value(),
            $report->updatedAt()->value(),
        );
    }

    public function update(SubtitleReport $report): void
    {
        $id = $report->id();

        if ($id === null) {
            throw new \LogicException('Cannot update a SubtitleReport without persisted id.');
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
        $base = $this->model->newQuery()
            ->where('status', SubtitleReportStatus::pending()->value());
        $total = (int) (clone $base)->count();

        $rows = $base
            ->orderBy('created_at')
            ->forPage($page, $perPage)
            ->get();

        $items = $rows->map(fn (EloquentSubtitleReport $model): SubtitleReport => $this->toDomainEntity($model))->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'totalPages' => $total > 0 ? (int) ceil($total / max(1, $perPage)) : 0,
        ];
    }

    private function resolveSubtitleInternalId(Uuid $subtitleId): ?int
    {
        $id = EloquentSubtitle::query()
            ->withTrashed()
            ->where('uuid', $subtitleId->value())
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function toDomainEntity(EloquentSubtitleReport $model): SubtitleReport
    {
        $subtitleUuid = EloquentSubtitle::query()
            ->withTrashed()
            ->where('id', $model->subtitle_id)
            ->value('uuid');
        $userUuid = $this->userIdResolver->toDomainUuid((int) $model->reported_by_user_id);

        return SubtitleReport::fromPersistence(
            (int) $model->id,
            (string) $subtitleUuid,
            $userUuid->value(),
            (string) $model->reason,
            (string) $model->status,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
