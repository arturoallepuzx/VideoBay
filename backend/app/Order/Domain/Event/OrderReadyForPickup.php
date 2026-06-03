<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Order\Domain\ValueObject\PickupCode;
use App\Shared\Domain\ValueObject\Uuid;

class OrderReadyForPickup
{
    private function __construct(
        private Uuid $orderId,
        private Uuid $userId,
        private PickupCode $pickupCode,
        private int $totalCents,
    ) {}

    public static function create(Uuid $orderId, Uuid $userId, PickupCode $pickupCode, int $totalCents): self
    {
        return new self($orderId, $userId, $pickupCode, $totalCents);
    }

    public function orderId(): Uuid
    {
        return $this->orderId;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function pickupCode(): PickupCode
    {
        return $this->pickupCode;
    }

    public function totalCents(): int
    {
        return $this->totalCents;
    }
}
