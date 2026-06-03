<?php

declare(strict_types=1);

namespace App\Inventory\Application\ConfirmStockSale;

use App\Inventory\Domain\Entity\PhysicalCopy;

final readonly class ConfirmStockSaleResponse
{
    private function __construct(
        public string $id,
        public int $stockAvailable,
        public int $stockReserved,
    ) {}

    public static function create(PhysicalCopy $copy): self
    {
        return new self(
            id: $copy->id()->value(),
            stockAvailable: $copy->stockAvailable()->value(),
            stockReserved: $copy->stockReserved()->value(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'stock_available' => $this->stockAvailable,
            'stock_reserved' => $this->stockReserved,
        ];
    }
}
