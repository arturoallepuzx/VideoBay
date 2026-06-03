<?php

declare(strict_types=1);

namespace App\Notification\Application\MarkNotificationAsRead;

use App\Notification\Domain\Exception\NotificationNotFoundException;
use App\Notification\Domain\Interfaces\NotificationRepositoryInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class MarkNotificationAsRead
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(string $userUuid, string $notificationUuid): void
    {
        $userId = Uuid::create($userUuid);
        $notificationId = Uuid::create($notificationUuid);

        $this->transactionRunner->run(function () use ($userId, $notificationId): void {
            $notification = $this->notificationRepository->findByUuidAndUserIdForUpdate($notificationId, $userId);

            if ($notification === null) {
                throw NotificationNotFoundException::forUuid($notificationId);
            }

            $notification->markAsRead(DomainDateTime::now());

            if ($notification->wasModified()) {
                $this->notificationRepository->update($notification);
            }
        });
    }
}
