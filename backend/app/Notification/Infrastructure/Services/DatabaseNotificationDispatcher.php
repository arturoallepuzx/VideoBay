<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Services;

use App\Notification\Domain\Entity\Notification;
use App\Notification\Domain\Interfaces\NotificationRepositoryInterface;
use App\Notification\Domain\ValueObject\NotificationMetadata;
use App\Notification\Domain\ValueObject\NotificationTitle;
use App\Notification\Domain\ValueObject\NotificationType;
use App\Shared\Domain\Interfaces\NotificationDispatcherInterface;
use App\Shared\Domain\ValueObject\Uuid;

class DatabaseNotificationDispatcher implements NotificationDispatcherInterface
{
    public function __construct(private NotificationRepositoryInterface $notificationRepository) {}

    public function sendToUser(
        Uuid $userId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null,
        array $metadata = [],
    ): void {
        $this->notificationRepository->create(Notification::dddCreate(
            $userId,
            NotificationType::create($type),
            NotificationTitle::create($title),
            $body,
            $actionUrl,
            NotificationMetadata::create($metadata),
        ));
    }
}
