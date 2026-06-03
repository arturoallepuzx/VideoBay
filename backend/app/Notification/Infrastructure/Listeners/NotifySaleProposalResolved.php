<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Listeners;

use App\Inventory\Domain\Event\SaleProposalResolved;
use App\Inventory\Domain\ValueObject\SaleProposalStatus;
use App\Notification\Domain\ValueObject\NotificationType;
use App\Notification\Infrastructure\Services\NotificationMetadataFactory;
use App\Shared\Domain\Interfaces\NotificationDispatcherInterface;

class NotifySaleProposalResolved
{
    public function __construct(
        private NotificationDispatcherInterface $notifications,
        private NotificationMetadataFactory $metadataFactory,
    ) {}

    public function handle(SaleProposalResolved $event): void
    {
        $accepted = $event->status()->equals(SaleProposalStatus::accepted());
        $movie = $event->movieId() !== null ? $this->metadataFactory->movie($event->movieId()) : null;
        $title = $event->titleText() ?? ($movie['title'] ?? null);

        $this->notifications->sendToUser(
            $event->userId(),
            $accepted ? NotificationType::PROPOSAL_ACCEPTED : NotificationType::PROPOSAL_REJECTED,
            $accepted ? 'Tu propuesta ha sido aceptada' : 'Tu propuesta ha sido rechazada',
            $title !== null ? sprintf('Propuesta: %s', $title) : null,
            '/sell',
            [
                'proposal' => [
                    'uuid' => $event->proposalId()->value(),
                    'status' => $event->status()->value(),
                    'title' => $title,
                    'format' => $event->format()->value(),
                ],
                'movie' => $movie,
            ],
        );
    }
}
