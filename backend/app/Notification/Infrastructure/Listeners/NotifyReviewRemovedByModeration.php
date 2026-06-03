<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Listeners;

use App\Notification\Domain\ValueObject\NotificationType;
use App\Notification\Infrastructure\Services\NotificationMetadataFactory;
use App\Review\Domain\Event\ReviewRemovedByModeration;
use App\Shared\Domain\Interfaces\NotificationDispatcherInterface;

class NotifyReviewRemovedByModeration
{
    public function __construct(
        private NotificationDispatcherInterface $notifications,
        private NotificationMetadataFactory $metadataFactory,
    ) {}

    public function handle(ReviewRemovedByModeration $event): void
    {
        $movie = $this->metadataFactory->movie($event->movieId());

        $this->notifications->sendToUser(
            $event->reviewAuthorId(),
            NotificationType::REVIEW_REMOVED_BY_MODERATION,
            'Tu reseña fue eliminada',
            'El equipo revisó un reporte y eliminó tu reseña.',
            '/movie/'.$event->movieId()->value(),
            [
                'movie' => $movie,
                'review' => $this->metadataFactory->review($event->reviewId(), $event->reviewBody()),
            ],
        );
    }
}
