<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

class CheckoutLineItem
{
    private function __construct(
        private string $name,
        private int $unitAmountCents,
        private int $quantity,
    ) {}

    public static function create(string $name, int $unitAmountCents, int $quantity): self
    {
        return new self($name, $unitAmountCents, $quantity);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function unitAmountCents(): int
    {
        return $this->unitAmountCents;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }
}
