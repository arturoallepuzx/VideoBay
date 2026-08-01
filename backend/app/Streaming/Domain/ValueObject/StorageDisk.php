<?php

declare(strict_types=1);

namespace App\Streaming\Domain\ValueObject;

final readonly class StorageDisk
{
    private const LOCAL = 'local';

    private const R2 = 'r2';

    private const VALID_DISKS = [
        self::LOCAL,
        self::R2,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_DISKS, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid storage disk "%s". Allowed: %s.', $value, implode(', ', self::VALID_DISKS))
            );
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function local(): self
    {
        return new self(self::LOCAL);
    }

    public static function r2(): self
    {
        return new self(self::R2);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isLocal(): bool
    {
        return $this->value === self::LOCAL;
    }

    public function isR2(): bool
    {
        return $this->value === self::R2;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
