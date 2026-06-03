<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

class BarcodeValue
{
    private const MIN_LENGTH = 8;

    private const MAX_LENGTH = 32;

    private const PATTERN = '/^\d+$/';

    private string $value;

    private function __construct(string $value)
    {
        $trimmed = trim($value);

        $length = strlen($trimmed);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Barcode length must be between %d and %d, got %d.', self::MIN_LENGTH, self::MAX_LENGTH, $length)
            );
        }

        if (! preg_match(self::PATTERN, $trimmed)) {
            throw new \InvalidArgumentException(sprintf('Barcode must contain only digits, got "%s".', $trimmed));
        }

        $this->value = $trimmed;
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
