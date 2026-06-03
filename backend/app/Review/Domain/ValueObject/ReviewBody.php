<?php

declare(strict_types=1);

namespace App\Review\Domain\ValueObject;

class ReviewBody
{
    private const MAX_LENGTH = 5000;

    private string $value;

    private function __construct(string $value)
    {
        $trimmed = trim($value);

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Review body cannot exceed %d characters.', self::MAX_LENGTH)
            );
        }

        $this->value = $trimmed;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
