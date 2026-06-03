<?php

declare(strict_types=1);

namespace App\Order\Application\GetOrderForPickup;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Entity\OrderItem;

final readonly class GetOrderForPickupResponse
{
    /** @param list<array<string, mixed>> $items */
    private function __construct(
        public string $id,
        public string $status,
        public int $totalCents,
        public ?string $pickupCode,
        public ?string $paidAt,
        public ?string $readyAt,
        public ?string $pickedUpAt,
        public ?string $cancelledAt,
        public string $createdAt,
        public array $items,
    ) {}

    public static function create(Order $order): self
    {
        $items = array_map(
            fn (OrderItem $item): array => [
                'physical_copy_id' => $item->physicalCopyId()->value(),
                'quantity' => $item->quantity()->value(),
                'unit_price_cents' => $item->unitPriceCents(),
                'subtotal_cents' => $item->subtotalCents(),
                'movie_title' => $item->movieTitleSnapshot(),
                'format' => $item->formatSnapshot(),
                'condition' => $item->conditionSnapshot(),
            ],
            $order->items(),
        );

        return new self(
            id: $order->id()->value(),
            status: $order->status()->value(),
            totalCents: $order->totalCents(),
            pickupCode: $order->pickupCode()?->value(),
            paidAt: $order->paidAt()?->value()->format(\DateTimeInterface::ATOM),
            readyAt: $order->readyAt()?->value()->format(\DateTimeInterface::ATOM),
            pickedUpAt: $order->pickedUpAt()?->value()->format(\DateTimeInterface::ATOM),
            cancelledAt: $order->cancelledAt()?->value()->format(\DateTimeInterface::ATOM),
            createdAt: $order->createdAt()->value()->format(\DateTimeInterface::ATOM),
            items: array_values($items),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'total_cents' => $this->totalCents,
            'pickup_code' => $this->pickupCode,
            'paid_at' => $this->paidAt,
            'ready_at' => $this->readyAt,
            'picked_up_at' => $this->pickedUpAt,
            'cancelled_at' => $this->cancelledAt,
            'created_at' => $this->createdAt,
            'items' => $this->items,
        ];
    }
}
