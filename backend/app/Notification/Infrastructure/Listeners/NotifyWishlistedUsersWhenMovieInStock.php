<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Listeners;

use App\Inventory\Domain\Event\PhysicalCopyAvailableForSale;
use App\Notification\Domain\ValueObject\NotificationType;
use App\Notification\Infrastructure\Services\NotificationMetadataFactory;
use App\Shared\Domain\Interfaces\NotificationDispatcherInterface;
use App\Wishlist\Domain\Interfaces\WishlistItemRepositoryInterface;

class NotifyWishlistedUsersWhenMovieInStock
{
    public function __construct(
        private WishlistItemRepositoryInterface $wishlistRepository,
        private NotificationDispatcherInterface $notifications,
        private NotificationMetadataFactory $metadataFactory,
    ) {}

    public function handle(PhysicalCopyAvailableForSale $event): void
    {
        $movie = $this->metadataFactory->movie($event->movieId());
        $body = is_string($movie['title'] ?? null) ? (string) $movie['title'] : null;

        foreach ($this->wishlistRepository->listUserIdsByMovie($event->movieId()) as $userId) {
            $this->notifications->sendToUser(
                $userId,
                NotificationType::WISHLIST_NOW_IN_STOCK,
                'Una película de tu wishlist está disponible',
                $body,
                '/movie/'.$event->movieId()->value(),
                [
                    'movie' => $movie,
                    'copy' => ['uuid' => $event->copyId()->value()],
                ],
            );
        }
    }
}
