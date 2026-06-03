<?php

declare(strict_types=1);

namespace App\Inventory\Application\GetPricingRules;

use App\Inventory\Domain\Exception\PricingSettingsNotFoundException;
use App\Inventory\Domain\Interfaces\PricingSettingsRepositoryInterface;
use App\Shared\Domain\Interfaces\SystemCurrencyProviderInterface;

class GetPricingRules
{
    private string $currency;

    public function __construct(
        private PricingSettingsRepositoryInterface $pricingSettingsRepository,
        SystemCurrencyProviderInterface $currencyProvider,
    ) {
        $this->currency = $currencyProvider->getCurrency();
    }

    public function __invoke(): GetPricingRulesResponse
    {
        $settings = $this->pricingSettingsRepository->findCurrent();

        if ($settings === null) {
            throw PricingSettingsNotFoundException::notSeeded();
        }

        return GetPricingRulesResponse::create($settings, $this->currency);
    }
}
