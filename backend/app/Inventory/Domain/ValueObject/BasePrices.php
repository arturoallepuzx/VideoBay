<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObject;

class BasePrices
{
    /** @var array<string, int> */
    private array $cents;

    /** @param array<string, int> $cents */
    private function __construct(array $cents)
    {
        foreach ($cents as $format => $amount) {
            if (! in_array($format, CopyFormat::allowed(), true)) {
                throw new \InvalidArgumentException(
                    sprintf('Unknown format "%s" in base prices.', $format)
                );
            }

            if (! is_int($amount) || $amount < 0) {
                throw new \InvalidArgumentException(
                    sprintf('Base price for "%s" must be a non-negative int (cents), got "%s".', $format, var_export($amount, true))
                );
            }
        }

        $this->cents = $cents;
    }

    /** @param array<string, int> $cents */
    public static function create(array $cents): self
    {
        return new self($cents);
    }

    public function forFormat(CopyFormat $format): int
    {
        return $this->cents[$format->value()] ?? 0;
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return $this->cents;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }
}
