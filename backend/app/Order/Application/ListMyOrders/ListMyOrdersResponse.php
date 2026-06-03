<?php

declare(strict_types=1);

namespace App\Order\Application\ListMyOrders;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Entity\OrderItem;

final readonly class ListMyOrdersResponse
{
    /** @param list<array<string, mixed>> $orders */
    private function __construct(
        public array $orders,
        public int $page,
        public int $totalPages,
        public int $total,
    ) {}

    /**
     * @param  array{orders: list<Order>, total: int, page: int, totalPages: int}  $result
     */
    public static function create(array $result): self
    {
        $orders = array_map(
            fn (Order $order): array => self::orderToArray($order),
            $result['orders'],
        );

        return new self(
            orders: array_values($orders),
            page: $result['page'],
            totalPages: $result['totalPages'],
            total: $result['total'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'orders' => $this->orders,
            'page' => $this->page,
            'total_pages' => $this->totalPages,
            'total' => $this->total,
        ];
    }

    /** @return array<string, mixed> */
    private static function orderToArray(Order $order): array
    {
        return [
            'id' => $order->id()->value(),
            'status' => $order->status()->value(),
            'total_cents' => $order->totalCents(),
            'pickup_code' => $order->pickupCode()?->value(),
            'paid_at' => $order->paidAt()?->value()->format(\DateTimeInterface::ATOM),
            'ready_at' => $order->readyAt()?->value()->format(\DateTimeInterface::ATOM),
            'picked_up_at' => $order->pickedUpAt()?->value()->format(\DateTimeInterface::ATOM),
            'cancelled_at' => $order->cancelledAt()?->value()->format(\DateTimeInterface::ATOM),
            'expires_at' => $order->expiresAt()?->value()->format(\DateTimeInterface::ATOM),
            'created_at' => $order->createdAt()->value()->format(\DateTimeInterface::ATOM),
            'items' => array_map(
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
            ),
        ];
    }
}
