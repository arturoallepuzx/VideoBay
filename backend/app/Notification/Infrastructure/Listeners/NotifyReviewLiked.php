<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Listeners;

use App\Notification\Domain\ValueObject\NotificationType;
use App\Notification\Infrastructure\Services\NotificationMetadataFactory;
use App\Review\Domain\Event\ReviewLiked;
use App\Shared\Domain\Interfaces\NotificationDispatcherInterface;

class NotifyReviewLiked
{
    public function __construct(
        private NotificationDispatcherInterface $notifications,
        private NotificationMetadataFactory $metadataFactory,
    ) {}

    public function handle(ReviewLiked $event): void
    {
        if ($event->reviewAuthorId()->equals($event->likedByUserId())) {
            return;
        }

        $actor = $this->metadataFactory->user($event->likedByUserId());
        $movie = $this->metadataFactory->movie($event->movieId());
        $actorName = is_string($actor['name'] ?? null) ? $actor['name'] : 'Alguien';

        $this->notifications->sendToUser(
            $event->reviewAuthorId(),
            NotificationType::REVIEW_LIKED,
            sprintf('%s le dio like a tu reseña', $actorName),
            is_string($movie['title'] ?? null) ? (string) $movie['title'] : null,
            '/movie/'.$event->movieId()->value(),
            [
                'actor' => $actor,
                'movie' => $movie,
                'review' => $this->metadataFactory->review($event->reviewId(), $event->reviewBody()),
            ],
        );
    }
}
