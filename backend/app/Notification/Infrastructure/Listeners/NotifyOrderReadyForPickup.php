<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Listeners;

use App\Notification\Domain\ValueObject\NotificationType;
use App\Order\Domain\Event\OrderReadyForPickup;
use App\Shared\Domain\Interfaces\NotificationDispatcherInterface;

class NotifyOrderReadyForPickup
{
    public function __construct(private NotificationDispatcherInterface $notifications) {}

    public function handle(OrderReadyForPickup $event): void
    {
        $this->notifications->sendToUser(
            $event->userId(),
            NotificationType::ORDER_READY_FOR_PICKUP,
            'Tu pedido está listo para recoger',
            'Ya puedes pasar por la tienda con tu código de recogida.',
            '/orders',
            [
                'order' => [
                    'uuid' => $event->orderId()->value(),
                    'pickup_code' => $event->pickupCode()->value(),
                    'total_cents' => $event->totalCents(),
                ],
            ],
        );
    }
}
