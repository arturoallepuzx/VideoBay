<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Order\Domain\ValueObject\PickupCode;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class OrderNotFoundException extends NotFoundException
{
    public static function forUuid(Uuid $uuid): self
    {
        return new self(sprintf('Order "%s" not found.', $uuid->value()));
    }

    public static function forPickupCode(PickupCode $pickupCode): self
    {
        return new self(sprintf('Order with pickup code "%s" not found.', $pickupCode->value()));
    }
}
