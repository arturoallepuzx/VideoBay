<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

class MoneyAmount
{
    private const CURRENCY_PATTERN = '/^[A-Z]{3}$/';

    private function __construct(
        private int $cents,
        private string $currency,
    ) {
        if ($cents < 0) {
            throw new \InvalidArgumentException(sprintf('Money amount cannot be negative, got %d.', $cents));
        }

        if (! preg_match(self::CURRENCY_PATTERN, $currency)) {
            throw new \InvalidArgumentException(sprintf('Currency must be a 3-letter ISO 4217 code, got "%s".', $currency));
        }
    }

    public static function create(int $cents, string $currency): self
    {
        return new self($cents, $currency);
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function multiply(float $factor): self
    {
        if ($factor < 0) {
            throw new \InvalidArgumentException(sprintf('Multiply factor cannot be negative, got %f.', $factor));
        }

        return new self((int) round($this->cents * $factor), $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents && $this->currency === $other->currency;
    }
}
