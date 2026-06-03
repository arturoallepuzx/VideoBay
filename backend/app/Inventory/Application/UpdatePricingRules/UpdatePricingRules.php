<?php

declare(strict_types=1);

namespace App\Inventory\Application\UpdatePricingRules;

use App\Inventory\Domain\Exception\PricingSettingsNotFoundException;
use App\Inventory\Domain\Interfaces\PricingSettingsRepositoryInterface;
use App\Inventory\Domain\ValueObject\BasePrices;
use App\Inventory\Domain\ValueObject\ConditionMultipliers;
use App\Inventory\Domain\ValueObject\PricingMargin;
use App\Shared\Domain\Interfaces\SystemCurrencyProviderInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;

class UpdatePricingRules
{
    private string $currency;

    public function __construct(
        private PricingSettingsRepositoryInterface $pricingSettingsRepository,
        private TransactionRunnerInterface $transactionRunner,
        SystemCurrencyProviderInterface $currencyProvider,
    ) {
        $this->currency = $currencyProvider->getCurrency();
    }

    /**
     * @param  array<string, int>|null  $basePricesCents
     * @param  array<string, float>|null  $conditionMultipliers
     */
    public function __invoke(
        ?array $basePricesCents,
        ?array $conditionMultipliers,
        ?int $buyMarginPercent,
    ): UpdatePricingRulesResponse {
        $settings = $this->transactionRunner->run(function () use ($basePricesCents, $conditionMultipliers, $buyMarginPercent) {
            $settings = $this->pricingSettingsRepository->findCurrentForUpdate();

            if ($settings === null) {
                throw PricingSettingsNotFoundException::notSeeded();
            }

            if ($basePricesCents !== null) {
                $merged = array_merge($settings->basePrices()->toArray(), $basePricesCents);
                $settings->updateBasePrices(BasePrices::create($merged));
            }

            if ($conditionMultipliers !== null) {
                $merged = array_merge($settings->conditionMultipliers()->toArray(), $conditionMultipliers);
                $settings->updateConditionMultipliers(ConditionMultipliers::create($merged));
            }

            if ($buyMarginPercent !== null) {
                $settings->updateBuyMargin(PricingMargin::create($buyMarginPercent));
            }

            if ($settings->wasModified()) {
                $this->pricingSettingsRepository->update($settings);
            }

            return $settings;
        });

        return UpdatePricingRulesResponse::create($settings, $this->currency);
    }
}
