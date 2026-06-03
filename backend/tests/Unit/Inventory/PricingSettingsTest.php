<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Inventory\Domain\Entity\PricingSettings;
use App\Inventory\Domain\ValueObject\CopyCondition;
use App\Inventory\Domain\ValueObject\CopyFormat;
use App\Inventory\Domain\ValueObject\PricingMargin;
use PHPUnit\Framework\TestCase;

class PricingSettingsTest extends TestCase
{
    private function makeSettings(): PricingSettings
    {
        return PricingSettings::fromPersistence(
            ['BLURAY' => 2000, 'DVD' => 1000],
            ['new' => 1.0, 'good' => 0.7],
            60,
            new \DateTimeImmutable,
        );
    }

    public function test_estimate_sell_price_applies_condition_multiplier(): void
    {
        $settings = $this->makeSettings();

        $price = $settings->estimateSellPrice(
            CopyFormat::create('BLURAY'),
            CopyCondition::create('good'),
            'EUR',
        );

        // 2000 base * 0.7 good = 1400
        $this->assertSame(1400, $price->cents());
        $this->assertSame('EUR', $price->currency());
    }

    public function test_estimate_buy_price_applies_margin_over_sell_price(): void
    {
        $settings = $this->makeSettings();

        $buyPrice = $settings->estimateBuyPrice(
            CopyFormat::create('BLURAY'),
            CopyCondition::create('new'),
            'EUR',
        );

        // sell = 2000 * 1.0 = 2000; buy = 2000 * 0.60 margin = 1200
        $this->assertSame(1200, $buyPrice->cents());
    }

    public function test_update_buy_margin_marks_modified(): void
    {
        $settings = $this->makeSettings();

        $settings->updateBuyMargin(PricingMargin::create(50));

        $this->assertTrue($settings->wasModified());
        $this->assertSame(50, $settings->buyMargin()->percent());
    }

    public function test_update_buy_margin_with_same_value_does_not_mark_modified(): void
    {
        $settings = $this->makeSettings();

        $settings->updateBuyMargin(PricingMargin::create(60));

        $this->assertFalse($settings->wasModified());
    }
}
