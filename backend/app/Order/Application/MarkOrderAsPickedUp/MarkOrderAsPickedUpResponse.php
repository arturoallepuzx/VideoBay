<?php

declare(strict_types=1);

namespace App\Order\Application\MarkOrderAsPickedUp;

use App\Order\Domain\Entity\Order;

final readonly class MarkOrderAsPickedUpResponse
{
    private function __construct(
        public string $id,
        public string $status,
        public ?string $pickedUpAt,
    ) {}

    public static function create(Order $order): self
    {
        return new self(
            id: $order->id()->value(),
            status: $order->status()->value(),
            pickedUpAt: $order->pickedUpAt()?->value()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'picked_up_at' => $this->pickedUpAt,
        ];
    }
}
