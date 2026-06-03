<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\Exception\ConflictException;
use App\Shared\Domain\ValueObject\Uuid;

class InvalidOrderTransitionException extends ConflictException
{
    public static function cannotPay(Uuid $id, OrderStatus $current): self
    {
        return new self(sprintf(
            'Cannot mark order "%s" as paid: current status is "%s" (must be "pending_payment").',
            $id->value(),
            $current->value(),
        ));
    }

    public static function cannotReadyForPickup(Uuid $id, OrderStatus $current): self
    {
        return new self(sprintf(
            'Cannot mark order "%s" as ready for pickup: current status is "%s" (must be "paid").',
            $id->value(),
            $current->value(),
        ));
    }

    public static function cannotPickUp(Uuid $id, OrderStatus $current): self
    {
        return new self(sprintf(
            'Cannot mark order "%s" as picked up: current status is "%s" (must be "ready_for_pickup").',
            $id->value(),
            $current->value(),
        ));
    }

    public static function cannotCancel(Uuid $id, OrderStatus $current): self
    {
        return new self(sprintf(
            'Cannot cancel order "%s": current status is "%s" (must be "pending_payment").',
            $id->value(),
            $current->value(),
        ));
    }
}
