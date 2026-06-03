<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

class PickupCode
{
    private const LENGTH = 10;

    private const MAX_LENGTH = 16;

    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private string $value;

    private function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if ($normalized === '' || mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Pickup code must be between 1 and %d characters.', self::MAX_LENGTH)
            );
        }

        if (preg_match('/^['.self::ALPHABET.']+$/', $normalized) !== 1) {
            throw new \InvalidArgumentException(
                sprintf('Pickup code "%s" contains invalid characters.', $value)
            );
        }

        $this->value = $normalized;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function generate(): self
    {
        $alphabetLength = strlen(self::ALPHABET);
        $code = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        return new self($code);
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
