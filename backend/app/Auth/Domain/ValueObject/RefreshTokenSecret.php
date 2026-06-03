<?php

declare(strict_types=1);

namespace App\Auth\Domain\ValueObject;

class RefreshTokenSecret
{
    private const PATTERN = '/^[A-Za-z0-9_\-]{43}$/';

    private const HASH_ALGO = 'sha256';

    private string $value;

    private function __construct(string $value)
    {
        if (! preg_match(self::PATTERN, $value)) {
            throw new \InvalidArgumentException(
                'Refresh token secret must be 43 characters of base64url (32 random bytes, no padding).'
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

    public function hash(): RefreshTokenHash
    {
        return RefreshTokenHash::create(hash(self::HASH_ALGO, $this->value));
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }
}
