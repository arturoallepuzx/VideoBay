<?php

namespace Database\Seeders;

use App\Inventory\Infrastructure\Persistence\Models\EloquentPricingSettings;
use Illuminate\Database\Seeder;

class PricingSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (EloquentPricingSettings::query()->exists()) {
            return;
        }

        EloquentPricingSettings::query()->create([
            'base_prices' => [
                'DVD' => 500,
                'BLURAY' => 1000,
                'UHD_4K' => 1600,
                'VHS' => 600,
            ],
            'condition_multipliers' => [
                'new' => 1.0,
                'like_new' => 0.75,
                'good' => 0.5,
                'fair' => 0.25,
            ],
            'buy_margin_percent' => 10,
            'updated_at' => now(),
        ]);
    }
}
