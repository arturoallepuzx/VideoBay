<?php

declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\Uuid;

class CartItem
{
    private function __construct(
        private Uuid $physicalCopyId,
        private Quantity $quantity,
    ) {}

    public static function create(Uuid $physicalCopyId, Quantity $quantity): self
    {
        return new self($physicalCopyId, $quantity);
    }

    public function physicalCopyId(): Uuid
    {
        return $this->physicalCopyId;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function increaseQuantity(int $amount): void
    {
        $this->quantity = $this->quantity->add($amount);
    }

    public function changeQuantity(Quantity $quantity): void
    {
        $this->quantity = $quantity;
    }
}
