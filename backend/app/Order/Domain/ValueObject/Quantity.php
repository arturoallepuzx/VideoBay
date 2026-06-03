<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

class Quantity
{
    private int $value;

    private function __construct(int $value)
    {
        if ($value < 1) {
            throw new \InvalidArgumentException(sprintf('Quantity must be at least 1, got %d.', $value));
        }

        $this->value = $value;
    }

    public static function create(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function add(int $amount): self
    {
        return new self($this->value + $amount);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
