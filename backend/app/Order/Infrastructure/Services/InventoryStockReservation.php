<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Services;

use App\Inventory\Application\ConfirmStockSale\ConfirmStockSale;
use App\Inventory\Application\ReleaseReservedStock\ReleaseReservedStock;
use App\Inventory\Application\ReserveStock\ReserveStock;
use App\Order\Domain\Interfaces\StockReservationInterface;
use App\Shared\Domain\ValueObject\Uuid;

class InventoryStockReservation implements StockReservationInterface
{
    public function __construct(
        private ReserveStock $reserveStock,
        private ReleaseReservedStock $releaseReservedStock,
        private ConfirmStockSale $confirmStockSale,
    ) {}

    public function reserve(Uuid $physicalCopyId, int $quantity): void
    {
        ($this->reserveStock)($physicalCopyId->value(), $quantity);
    }

    public function release(Uuid $physicalCopyId, int $quantity): void
    {
        ($this->releaseReservedStock)($physicalCopyId->value(), $quantity);
    }

    public function confirmSale(Uuid $physicalCopyId, int $quantity): void
    {
        ($this->confirmStockSale)($physicalCopyId->value(), $quantity);
    }
}
