<?php

declare(strict_types=1);

namespace App\Notification\Application\ListMyNotifications;

use App\Notification\Domain\Entity\Notification;

final readonly class ListMyNotificationsResponse
{
    /** @param list<array<string, mixed>> $items */
    private function __construct(
        public array $items,
        public int $page,
        public int $totalPages,
        public int $total,
    ) {}

    /**
     * @param  array{items: list<Notification>, total: int, page: int, totalPages: int}  $result
     */
    public static function create(array $result): self
    {
        return new self(
            items: array_map(
                fn (Notification $notification): array => self::notificationToArray($notification),
                $result['items'],
            ),
            page: $result['page'],
            totalPages: $result['totalPages'],
            total: $result['total'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'page' => $this->page,
            'total_pages' => $this->totalPages,
            'total' => $this->total,
        ];
    }

    /** @return array<string, mixed> */
    private static function notificationToArray(Notification $notification): array
    {
        return [
            'uuid' => $notification->id()->value(),
            'type' => $notification->type()->value(),
            'title' => $notification->title()->value(),
            'body' => $notification->body(),
            'action_url' => $notification->actionUrl(),
            'metadata' => $notification->metadata()->toArray(),
            'read_at' => $notification->readAt()?->value()->format(\DateTimeInterface::ATOM),
            'created_at' => $notification->createdAt()->value()->format(\DateTimeInterface::ATOM),
        ];
    }
}
