<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\ValueObject;

class SubtitleLabel
{
    private const MAX_LENGTH = 80;

    private string $value;

    private function __construct(string $value)
    {
        $normalized = trim($value);

        if ($normalized === '' || mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf('Subtitle label must be between 1 and %d characters.', self::MAX_LENGTH));
        }

        $this->value = $normalized;
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
