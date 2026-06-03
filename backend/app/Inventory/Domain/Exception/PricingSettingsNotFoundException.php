<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

class PricingSettingsNotFoundException extends NotFoundException
{
    public static function notSeeded(): self
    {
        return new self('Pricing settings have not been initialized.');
    }
}
