<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

class EmptyCartException extends ConflictException
{
    public static function cannotCheckout(): self
    {
        return new self('Cannot start checkout with an empty cart.');
    }
}
