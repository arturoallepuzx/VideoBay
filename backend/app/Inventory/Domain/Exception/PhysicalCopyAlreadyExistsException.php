<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Exception;

use App\Inventory\Domain\ValueObject\SkuCode;
use App\Shared\Domain\Exception\ConflictException;

class PhysicalCopyAlreadyExistsException extends ConflictException
{
    public static function forSku(SkuCode $sku): self
    {
        return new self(sprintf('Physical copy with SKU "%s" already exists.', $sku->value()));
    }
}
