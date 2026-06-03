<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObject;

class PricingMargin
{
    private int $percent;

    private function __construct(int $percent)
    {
        if ($percent < 0 || $percent > 100) {
            throw new \InvalidArgumentException(
                sprintf('Pricing margin must be between 0 and 100, got %d.', $percent)
            );
        }

        $this->percent = $percent;
    }

    public static function create(int $percent): self
    {
        return new self($percent);
    }

    public function percent(): int
    {
        return $this->percent;
    }

    public function asFactor(): float
    {
        return $this->percent / 100.0;
    }

    public function equals(self $other): bool
    {
        return $this->percent === $other->percent;
    }
}
