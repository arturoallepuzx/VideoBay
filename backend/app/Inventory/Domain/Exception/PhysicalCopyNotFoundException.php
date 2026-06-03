<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Exception;

use App\Inventory\Domain\ValueObject\SkuCode;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class PhysicalCopyNotFoundException extends NotFoundException
{
    public static function forUuid(Uuid $uuid): self
    {
        return new self(sprintf('Physical copy "%s" not found.', $uuid->value()));
    }

    public static function forSku(SkuCode $sku): self
    {
        return new self(sprintf('Physical copy with SKU "%s" not found.', $sku->value()));
    }
}
