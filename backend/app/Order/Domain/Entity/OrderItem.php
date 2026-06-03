<?php

declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\Uuid;

class OrderItem
{
    private function __construct(
        private Uuid $physicalCopyId,
        private Quantity $quantity,
        private int $unitPriceCents,
        private string $movieTitleSnapshot,
        private string $formatSnapshot,
        private string $conditionSnapshot,
    ) {}

    public static function create(
        Uuid $physicalCopyId,
        Quantity $quantity,
        int $unitPriceCents,
        string $movieTitleSnapshot,
        string $formatSnapshot,
        string $conditionSnapshot,
    ): self {
        if ($unitPriceCents < 0) {
            throw new \InvalidArgumentException(sprintf('Unit price cannot be negative, got %d.', $unitPriceCents));
        }

        return new self(
            $physicalCopyId,
            $quantity,
            $unitPriceCents,
            $movieTitleSnapshot,
            $formatSnapshot,
            $conditionSnapshot,
        );
    }

    public function physicalCopyId(): Uuid
    {
        return $this->physicalCopyId;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function unitPriceCents(): int
    {
        return $this->unitPriceCents;
    }

    public function subtotalCents(): int
    {
        return $this->unitPriceCents * $this->quantity->value();
    }

    public function movieTitleSnapshot(): string
    {
        return $this->movieTitleSnapshot;
    }

    public function formatSnapshot(): string
    {
        return $this->formatSnapshot;
    }

    public function conditionSnapshot(): string
    {
        return $this->conditionSnapshot;
    }
}
