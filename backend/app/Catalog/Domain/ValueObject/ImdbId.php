<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

class ImdbId
{
    private const PATTERN = '/^tt\d{7,10}$/';

    private string $value;

    private function __construct(string $value)
    {
        if (! preg_match(self::PATTERN, $value)) {
            throw new \InvalidArgumentException(sprintf('Invalid IMDB id "%s". Expected format: tt followed by 7-10 digits.', $value));
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
