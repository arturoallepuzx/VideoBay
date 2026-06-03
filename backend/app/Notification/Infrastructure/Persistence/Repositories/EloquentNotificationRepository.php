<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Repositories;

use App\Notification\Domain\Entity\Notification;
use App\Notification\Domain\Interfaces\NotificationRepositoryInterface;
use App\Notification\Infrastructure\Persistence\Models\EloquentNotification;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(
        private EloquentNotification $model,
        private UserIdResolverInterface $userIdResolver,
    ) {}

    public function create(Notification $notification): void
    {
        $this->model->newQuery()->create([
            'uuid' => $notification->id()->value(),
            'user_id' => $this->userIdResolver->toInternalId($notification->userId()),
            'type' => $notification->type()->value(),
            'title' => $notification->title()->value(),
            'body' => $notification->body(),
            'action_url' => $notification->actionUrl(),
            'metadata' => $notification->metadata()->toArray(),
            'read_at' => $notification->readAt()?->value(),
            'created_at' => $notification->createdAt()->value(),
        ]);
    }

    public function update(Notification $notification): void
    {
        $this->model->newQuery()
            ->where('uuid', $notification->id()->value())
            ->update([
                'read_at' => $notification->readAt()?->value(),
            ]);
    }

    public function findByUuidAndUserIdForUpdate(Uuid $uuid, Uuid $userId): ?Notification
    {
        $model = $this->model->newQuery()
            ->where('uuid', $uuid->value())
            ->where('user_id', $this->userIdResolver->toInternalId($userId))
            ->lockForUpdate()
            ->first();

        return $model !== null ? $this->toDomainEntity($model, $userId) : null;
    }

    public function listByUser(Uuid $userId, int $page, int $perPage): array
    {
        $base = $this->model->newQuery()
            ->where('user_id', $this->userIdResolver->toInternalId($userId));

        $total = (int) (clone $base)->count();

        $items = (clone $base)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (EloquentNotification $model): Notification => $this->toDomainEntity($model, $userId))
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'totalPages' => $total > 0 ? (int) ceil($total / max(1, $perPage)) : 0,
        ];
    }

    public function markAllAsRead(Uuid $userId, DomainDateTime $readAt): int
    {
        return $this->model->newQuery()
            ->where('user_id', $this->userIdResolver->toInternalId($userId))
            ->whereNull('read_at')
            ->update(['read_at' => $readAt->value()]);
    }

    private function toDomainEntity(EloquentNotification $model, Uuid $userId): Notification
    {
        return Notification::fromPersistence(
            (string) $model->uuid,
            $userId->value(),
            (string) $model->type,
            (string) $model->title,
            $model->body !== null ? (string) $model->body : null,
            $model->action_url !== null ? (string) $model->action_url : null,
            is_array($model->metadata) ? $model->metadata : [],
            $model->read_at?->toDateTimeImmutable(),
            $model->created_at->toDateTimeImmutable(),
        );
    }
}
