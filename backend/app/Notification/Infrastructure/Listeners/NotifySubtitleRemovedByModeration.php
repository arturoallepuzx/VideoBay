<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Listeners;

use App\Notification\Domain\ValueObject\NotificationType;
use App\Notification\Infrastructure\Services\NotificationMetadataFactory;
use App\Shared\Domain\Interfaces\NotificationDispatcherInterface;
use App\Subtitle\Domain\Event\SubtitleRemovedByModeration;

class NotifySubtitleRemovedByModeration
{
    public function __construct(
        private NotificationDispatcherInterface $notifications,
        private NotificationMetadataFactory $metadataFactory,
    ) {}

    public function handle(SubtitleRemovedByModeration $event): void
    {
        $movie = $this->metadataFactory->movie($event->movieId());

        $this->notifications->sendToUser(
            $event->uploadedByUserId(),
            NotificationType::SUBTITLE_REMOVED_BY_MODERATION,
            'Tu subtítulo fue eliminado',
            'El equipo revisó un reporte y eliminó el subtítulo.',
            '/movie/'.$event->movieId()->value(),
            [
                'movie' => $movie,
                'subtitle' => [
                    'uuid' => $event->subtitleId()->value(),
                    'language' => $event->language()->value(),
                    'label' => $event->label()->value(),
                ],
            ],
        );
    }
}
