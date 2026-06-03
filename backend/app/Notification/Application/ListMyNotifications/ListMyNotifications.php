<?php

declare(strict_types=1);

namespace App\Notification\Application\ListMyNotifications;

use App\Notification\Domain\Interfaces\NotificationRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class ListMyNotifications
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __construct(private NotificationRepositoryInterface $notificationRepository) {}

    public function __invoke(string $userUuid, int $page, int $perPage): ListMyNotificationsResponse
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE));

        return ListMyNotificationsResponse::create(
            $this->notificationRepository->listByUser(Uuid::create($userUuid), $page, $perPage),
        );
    }
}
