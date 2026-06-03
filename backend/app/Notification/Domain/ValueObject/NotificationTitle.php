<?php

declare(strict_types=1);

namespace App\Notification\Domain\ValueObject;

class NotificationTitle
{
    private const MAX_LENGTH = 255;

    private function __construct(private string $value) {}

    public static function create(string $value): self
    {
        $normalized = trim($value);

        if ($normalized === '' || mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf('Notification title must be between 1 and %d characters.', self::MAX_LENGTH));
        }

        return new self($normalized);
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
