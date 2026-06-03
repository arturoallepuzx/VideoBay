<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

class TmdbRating
{
    private const MIN_VALUE = 0.0;

    private const MAX_VALUE = 10.0;

    private float $value;

    private function __construct(float $value)
    {
        if ($value < self::MIN_VALUE || $value > self::MAX_VALUE) {
            throw new \InvalidArgumentException(
                sprintf('TMDB rating must be between %.1f and %.1f, got %.2f.', self::MIN_VALUE, self::MAX_VALUE, $value)
            );
        }

        $this->value = round($value, 2);
    }

    public static function create(float $value): self
    {
        return new self($value);
    }

    public function value(): float
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return abs($this->value - $other->value) < 0.005;
    }
}
