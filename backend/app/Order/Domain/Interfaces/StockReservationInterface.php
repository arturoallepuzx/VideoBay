<?php

declare(strict_types=1);

namespace App\Order\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;

interface StockReservationInterface
{
    public function reserve(Uuid $physicalCopyId, int $quantity): void;

    public function release(Uuid $physicalCopyId, int $quantity): void;

    public function confirmSale(Uuid $physicalCopyId, int $quantity): void;
}
