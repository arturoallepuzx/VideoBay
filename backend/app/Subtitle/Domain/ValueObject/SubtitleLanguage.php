<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\ValueObject;

class SubtitleLanguage
{
    private const MAX_LENGTH = 10;

    private string $value;

    private function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if ($normalized === '' || strlen($normalized) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf('Subtitle language must be between 1 and %d characters.', self::MAX_LENGTH));
        }

        if (preg_match('/^[a-z]{2,3}(-[a-z0-9]{2,8})?$/', $normalized) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid subtitle language "%s".', $value));
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
