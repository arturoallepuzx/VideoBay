<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Repositories;

use App\Inventory\Domain\Entity\PricingSettings;
use App\Inventory\Domain\Interfaces\PricingSettingsRepositoryInterface;
use App\Inventory\Infrastructure\Persistence\Models\EloquentPricingSettings;

class EloquentPricingSettingsRepository implements PricingSettingsRepositoryInterface
{
    public function __construct(
        private EloquentPricingSettings $model,
    ) {}

    public function findCurrent(): ?PricingSettings
    {
        $model = $this->model->newQuery()->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function findCurrentForUpdate(): ?PricingSettings
    {
        $model = $this->model->newQuery()->lockForUpdate()->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function update(PricingSettings $settings): void
    {
        $this->model->newQuery()
            ->update([
                'base_prices' => $settings->basePrices()->toArray(),
                'condition_multipliers' => $settings->conditionMultipliers()->toArray(),
                'buy_margin_percent' => $settings->buyMargin()->percent(),
                'updated_at' => $settings->updatedAt()->value(),
            ]);
    }

    private function toDomainEntity(EloquentPricingSettings $model): PricingSettings
    {
        return PricingSettings::fromPersistence(
            (array) $model->base_prices,
            (array) $model->condition_multipliers,
            (int) $model->buy_margin_percent,
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
