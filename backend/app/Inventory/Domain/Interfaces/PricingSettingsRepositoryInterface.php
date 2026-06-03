<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Interfaces;

use App\Inventory\Domain\Entity\PricingSettings;

interface PricingSettingsRepositoryInterface
{
    public function findCurrent(): ?PricingSettings;

    public function findCurrentForUpdate(): ?PricingSettings;

    public function update(PricingSettings $settings): void;
}
