<?php

declare(strict_types=1);

namespace App\Streaming\Domain\ValueObject;

class VideoFileProcessingStatus
{
    private const PENDING = 'pending';

    private const PROCESSING = 'processing';

    private const READY = 'ready';

    private const FAILED = 'failed';

    private const VALID_STATUSES = [
        self::PENDING,
        self::PROCESSING,
        self::READY,
        self::FAILED,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid video file processing status "%s". Allowed: %s.', $value, implode(', ', self::VALID_STATUSES))
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

    public static function processing(): self
    {
        return new self(self::PROCESSING);
    }

    public static function ready(): self
    {
        return new self(self::READY);
    }

    public static function failed(): self
    {
        return new self(self::FAILED);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->value === self::PROCESSING;
    }

    public function isReady(): bool
    {
        return $this->value === self::READY;
    }

    public function isFailed(): bool
    {
        return $this->value === self::FAILED;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
