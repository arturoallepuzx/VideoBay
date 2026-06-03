<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Services;

use App\Shared\Domain\Interfaces\SystemCurrencyProviderInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class ConfigSystemCurrencyProvider implements SystemCurrencyProviderInterface
{
    public function __construct(private ConfigRepository $config) {}

    public function getCurrency(): string
    {
        $currency = (string) $this->config->get('inventory.currency');

        if (trim($currency) === '') {
            throw new \InvalidArgumentException('INVENTORY_CURRENCY is required.');
        }

        return $currency;
    }
}
