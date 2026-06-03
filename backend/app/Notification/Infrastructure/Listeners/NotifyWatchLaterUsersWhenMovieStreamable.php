<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Listeners;

use App\Notification\Domain\ValueObject\NotificationType;
use App\Notification\Infrastructure\Services\NotificationMetadataFactory;
use App\Shared\Domain\Interfaces\NotificationDispatcherInterface;
use App\Streaming\Domain\Event\VideoFileReady;
use App\Wishlist\Domain\Interfaces\WatchLaterItemRepositoryInterface;

class NotifyWatchLaterUsersWhenMovieStreamable
{
    public function __construct(
        private WatchLaterItemRepositoryInterface $watchLaterRepository,
        private NotificationDispatcherInterface $notifications,
        private NotificationMetadataFactory $metadataFactory,
    ) {}

    public function handle(VideoFileReady $event): void
    {
        $movie = $this->metadataFactory->movie($event->movieId());
        $body = is_string($movie['title'] ?? null) ? (string) $movie['title'] : null;

        foreach ($this->watchLaterRepository->listUserIdsByMovie($event->movieId()) as $userId) {
            $this->notifications->sendToUser(
                $userId,
                NotificationType::WATCH_LATER_NOW_STREAMABLE,
                'Ya puedes ver una película de tu lista',
                $body,
                '/player/'.$event->movieId()->value(),
                [
                    'movie' => $movie,
                    'video_file' => ['uuid' => $event->videoFileId()->value()],
                ],
            );
        }
    }
}
