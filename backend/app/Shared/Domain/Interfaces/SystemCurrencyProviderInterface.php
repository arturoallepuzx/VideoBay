<?php

declare(strict_types=1);

namespace App\Shared\Domain\Interfaces;

interface SystemCurrencyProviderInterface
{
    public function getCurrency(): string;
}
