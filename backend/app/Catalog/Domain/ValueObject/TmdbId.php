<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

class TmdbId
{
    private int $value;

    private function __construct(int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException(sprintf('TMDB id must be a positive integer, got %d.', $value));
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
