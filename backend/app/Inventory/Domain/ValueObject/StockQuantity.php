<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObject;

class StockQuantity
{
    private int $value;

    private function __construct(int $value)
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(sprintf('Stock quantity cannot be negative, got %d.', $value));
        }

        $this->value = $value;
    }

    public static function create(int $value): self
    {
        return new self($value);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function isZero(): bool
    {
        return $this->value === 0;
    }

    public function add(int $amount): self
    {
        return new self($this->value + $amount);
    }

    public function subtract(int $amount): self
    {
        return new self($this->value - $amount);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
