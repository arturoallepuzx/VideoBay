<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

class RuntimeMinutes
{
    private const MAX_MINUTES = 32767;

    private int $value;

    private function __construct(int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException(sprintf('Runtime must be a positive integer, got %d.', $value));
        }

        if ($value > self::MAX_MINUTES) {
            throw new \InvalidArgumentException(
                sprintf('Runtime cannot exceed %d minutes.', self::MAX_MINUTES)
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
