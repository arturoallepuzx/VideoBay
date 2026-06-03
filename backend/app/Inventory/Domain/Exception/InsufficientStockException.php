<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Exception;

use App\Inventory\Domain\ValueObject\SkuCode;
use App\Shared\Domain\Exception\ConflictException;

class InsufficientStockException extends ConflictException
{
    public static function forReservation(SkuCode $sku, int $requested, int $available): self
    {
        return new self(
            sprintf(
                'Cannot reserve %d units of SKU "%s": only %d available.',
                $requested,
                $sku->value(),
                $available,
            )
        );
    }

    public static function forRelease(SkuCode $sku, int $requested, int $reserved): self
    {
        return new self(
            sprintf(
                'Cannot release %d reserved units of SKU "%s": only %d reserved.',
                $requested,
                $sku->value(),
                $reserved,
            )
        );
    }

    public static function forSaleConfirmation(SkuCode $sku, int $requested, int $reserved): self
    {
        return new self(
            sprintf(
                'Cannot confirm sale of %d units of SKU "%s": only %d reserved.',
                $requested,
                $sku->value(),
                $reserved,
            )
        );
    }
}
