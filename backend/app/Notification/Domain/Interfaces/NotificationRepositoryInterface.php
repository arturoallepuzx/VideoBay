<?php

declare(strict_types=1);

namespace App\Notification\Domain\Interfaces;

use App\Notification\Domain\Entity\Notification;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

interface NotificationRepositoryInterface
{
    public function create(Notification $notification): void;

    public function update(Notification $notification): void;

    public function findByUuidAndUserIdForUpdate(Uuid $uuid, Uuid $userId): ?Notification;

    /**
     * @return array{items: list<Notification>, total: int, page: int, totalPages: int}
     */
    public function listByUser(Uuid $userId, int $page, int $perPage): array;

    public function markAllAsRead(Uuid $userId, DomainDateTime $readAt): int;
}
