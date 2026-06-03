<?php

declare(strict_types=1);

namespace App\Inventory\Application\EstimateCopyPrice;

use App\Inventory\Domain\Exception\PricingSettingsNotFoundException;
use App\Inventory\Domain\Interfaces\PricingSettingsRepositoryInterface;
use App\Inventory\Domain\ValueObject\CopyCondition;
use App\Inventory\Domain\ValueObject\CopyFormat;
use App\Shared\Domain\Interfaces\SystemCurrencyProviderInterface;

class EstimateCopyPrice
{
    private string $currency;

    public function __construct(
        private PricingSettingsRepositoryInterface $pricingSettingsRepository,
        SystemCurrencyProviderInterface $currencyProvider,
    ) {
        $this->currency = $currencyProvider->getCurrency();
    }

    public function __invoke(string $format, string $condition): EstimateCopyPriceResponse
    {
        $settings = $this->pricingSettingsRepository->findCurrent();

        if ($settings === null) {
            throw PricingSettingsNotFoundException::notSeeded();
        }

        $formatVo = CopyFormat::create($format);
        $conditionVo = CopyCondition::create($condition);

        $sellPrice = $settings->estimateSellPrice($formatVo, $conditionVo, $this->currency);
        $buyPrice = $settings->estimateBuyPrice($formatVo, $conditionVo, $this->currency);

        return EstimateCopyPriceResponse::create($formatVo, $conditionVo, $sellPrice, $buyPrice);
    }
}
