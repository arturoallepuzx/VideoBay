<?php

declare(strict_types=1);

namespace App\Inventory\Application\UpdatePricingRules;

use App\Inventory\Domain\Entity\PricingSettings;

final readonly class UpdatePricingRulesResponse
{
    /**
     * @param  array<string, int>  $basePricesCents
     * @param  array<string, float>  $conditionMultipliers
     */
    private function __construct(
        public array $basePricesCents,
        public array $conditionMultipliers,
        public int $buyMarginPercent,
        public string $currency,
        public string $updatedAt,
    ) {}

    public static function create(PricingSettings $settings, string $currency): self
    {
        return new self(
            basePricesCents: $settings->basePrices()->toArray(),
            conditionMultipliers: $settings->conditionMultipliers()->toArray(),
            buyMarginPercent: $settings->buyMargin()->percent(),
            currency: $currency,
            updatedAt: $settings->updatedAt()->value()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'base_prices_cents' => $this->basePricesCents,
            'condition_multipliers' => $this->conditionMultipliers,
            'buy_margin_percent' => $this->buyMarginPercent,
            'currency' => $this->currency,
            'updated_at' => $this->updatedAt,
        ];
    }
}
