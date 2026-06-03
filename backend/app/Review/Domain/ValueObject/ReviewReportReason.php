<?php

declare(strict_types=1);

namespace App\Review\Domain\ValueObject;

class ReviewReportReason
{
    private const SPAM = 'spam';

    private const OFFENSIVE = 'offensive';

    private const HIDDEN_SPOILER = 'hidden_spoiler';

    private const OTHER = 'other';

    private const VALID_REASONS = [
        self::SPAM,
        self::OFFENSIVE,
        self::HIDDEN_SPOILER,
        self::OTHER,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_REASONS, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid review report reason "%s". Allowed: %s.', $value, implode(', ', self::VALID_REASONS))
            );
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
