<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

class BarcodeResolutionMethod
{
    private const MANUAL = 'manual';

    private const EXTERNAL_API = 'external_api';

    private const PHYSICAL_COPY = 'physical_copy';

    private const SALE_PROPOSAL = 'sale_proposal';

    private const VALID_METHODS = [
        self::MANUAL,
        self::EXTERNAL_API,
        self::PHYSICAL_COPY,
        self::SALE_PROPOSAL,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_METHODS, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid barcode resolution method "%s". Allowed: %s.',
                    $value,
                    implode(', ', self::VALID_METHODS),
                )
            );
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function manual(): self
    {
        return new self(self::MANUAL);
    }

    public static function externalApi(): self
    {
        return new self(self::EXTERNAL_API);
    }

    public static function physicalCopy(): self
    {
        return new self(self::PHYSICAL_COPY);
    }

    public static function saleProposal(): self
    {
        return new self(self::SALE_PROPOSAL);
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
