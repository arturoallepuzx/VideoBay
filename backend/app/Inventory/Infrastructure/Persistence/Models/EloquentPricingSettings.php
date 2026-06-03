<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentPricingSettings extends Model
{
    public $timestamps = false;

    protected $table = 'pricing_settings';

    protected $fillable = [
        'base_prices',
        'condition_multipliers',
        'buy_margin_percent',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'base_prices' => 'array',
            'condition_multipliers' => 'array',
            'buy_margin_percent' => 'integer',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
