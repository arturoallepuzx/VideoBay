<?php

declare(strict_types=1);

namespace App\Review\Domain\ValueObject;

class ReviewReportStatus
{
    private const PENDING = 'pending';

    private const RESOLVED = 'resolved';

    private const DISMISSED = 'dismissed';

    private const VALID_STATUSES = [
        self::PENDING,
        self::RESOLVED,
        self::DISMISSED,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid review report status "%s". Allowed: %s.', $value, implode(', ', self::VALID_STATUSES))
            );
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function resolved(): self
    {
        return new self(self::RESOLVED);
    }

    public static function dismissed(): self
    {
        return new self(self::DISMISSED);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
