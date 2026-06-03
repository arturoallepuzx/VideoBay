<?php

declare(strict_types=1);

namespace App\Auth\Domain\ValueObject;

class RefreshTokenHash
{
    private const PATTERN = '/^[a-f0-9]{64}$/';

    private string $value;

    private function __construct(string $value)
    {
        if (! preg_match(self::PATTERN, $value)) {
            throw new \InvalidArgumentException(
                'Refresh token hash must be a 64-character lowercase hex string (SHA-256).'
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
        return hash_equals($this->value, $other->value);
    }
}
