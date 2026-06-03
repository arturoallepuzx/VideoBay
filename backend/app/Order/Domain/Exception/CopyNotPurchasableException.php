<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;
use App\Shared\Domain\ValueObject\Uuid;

class CopyNotPurchasableException extends ConflictException
{
    public static function unavailable(Uuid $physicalCopyId): self
    {
        return new self(sprintf('Physical copy "%s" is not available for purchase.', $physicalCopyId->value()));
    }

    public static function insufficientStock(Uuid $physicalCopyId, int $requested, int $available): self
    {
        return new self(sprintf(
            'Cannot add %d units of physical copy "%s" to the cart: only %d available.',
            $requested,
            $physicalCopyId->value(),
            $available,
        ));
    }
}
