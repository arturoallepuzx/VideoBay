<?php

declare(strict_types=1);

namespace App\Notification\Application\MarkAllNotificationsAsRead;

use App\Notification\Domain\Interfaces\NotificationRepositoryInterface;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class MarkAllNotificationsAsRead
{
    public function __construct(private NotificationRepositoryInterface $notificationRepository) {}

    public function __invoke(string $userUuid): void
    {
        $this->notificationRepository->markAllAsRead(Uuid::create($userUuid), DomainDateTime::now());
    }
}
