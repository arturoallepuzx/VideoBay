<?php

declare(strict_types=1);

namespace App\Review\Domain\ValueObject;

class ReviewRating
{
    private const MIN = 1;

    private const MAX = 10;

    private int $value;

    private function __construct(int $value)
    {
        if ($value < self::MIN || $value > self::MAX) {
            throw new \InvalidArgumentException(
                sprintf('Review rating must be between %d and %d, got %d.', self::MIN, self::MAX, $value)
            );
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

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
