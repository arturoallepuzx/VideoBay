<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entity;

use App\Inventory\Domain\ValueObject\BasePrices;
use App\Inventory\Domain\ValueObject\ConditionMultipliers;
use App\Inventory\Domain\ValueObject\CopyCondition;
use App\Inventory\Domain\ValueObject\CopyFormat;
use App\Inventory\Domain\ValueObject\PricingMargin;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\MoneyAmount;

class PricingSettings
{
    private bool $modified = false;

    private function __construct(
        private BasePrices $basePrices,
        private ConditionMultipliers $conditionMultipliers,
        private PricingMargin $buyMargin,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        BasePrices $basePrices,
        ConditionMultipliers $conditionMultipliers,
        PricingMargin $buyMargin,
    ): self {
        return new self($basePrices, $conditionMultipliers, $buyMargin, DomainDateTime::now());
    }

    /**
     * @param  array<string, int>  $basePrices
     * @param  array<string, float>  $conditionMultipliers
     */
    public static function fromPersistence(
        array $basePrices,
        array $conditionMultipliers,
        int $buyMarginPercent,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            BasePrices::create($basePrices),
            ConditionMultipliers::create($conditionMultipliers),
            PricingMargin::create($buyMarginPercent),
            DomainDateTime::create($updatedAt),
        );
    }

    public function updateBasePrices(BasePrices $basePrices): void
    {
        if ($this->basePrices->equals($basePrices)) {
            return;
        }

        $this->basePrices = $basePrices;
        $this->touch();
    }

    public function updateConditionMultipliers(ConditionMultipliers $conditionMultipliers): void
    {
        if ($this->conditionMultipliers->equals($conditionMultipliers)) {
            return;
        }

        $this->conditionMultipliers = $conditionMultipliers;
        $this->touch();
    }

    public function updateBuyMargin(PricingMargin $buyMargin): void
    {
        if ($this->buyMargin->equals($buyMargin)) {
            return;
        }

        $this->buyMargin = $buyMargin;
        $this->touch();
    }

    public function estimateSellPrice(CopyFormat $format, CopyCondition $condition, string $currency): MoneyAmount
    {
        $baseCents = $this->basePrices->forFormat($format);
        $multiplier = $this->conditionMultipliers->forCondition($condition);
        $sellCents = (int) round($baseCents * $multiplier);

        return MoneyAmount::create($sellCents, $currency);
    }

    public function estimateBuyPrice(CopyFormat $format, CopyCondition $condition, string $currency): MoneyAmount
    {
        return $this->estimateSellPrice($format, $condition, $currency)->multiply($this->buyMargin->asFactor());
    }

    public function basePrices(): BasePrices
    {
        return $this->basePrices;
    }

    public function conditionMultipliers(): ConditionMultipliers
    {
        return $this->conditionMultipliers;
    }

    public function buyMargin(): PricingMargin
    {
        return $this->buyMargin;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function wasModified(): bool
    {
        return $this->modified;
    }

    private function touch(): void
    {
        $this->modified = true;
        $this->updatedAt = DomainDateTime::now();
    }
}
