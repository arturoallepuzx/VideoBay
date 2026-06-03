<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\ValueObject;

class SubtitleReportReason
{
    private const VALID_REASONS = [
        'out_of_sync',
        'wrong_language',
        'spam',
        'offensive',
        'other',
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_REASONS, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid subtitle report reason "%s".', $value));
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
