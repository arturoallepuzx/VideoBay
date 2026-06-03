<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentOrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'physical_copy_id',
        'quantity',
        'unit_price_cents',
        'movie_title_snapshot',
        'format_snapshot',
        'condition_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
        ];
    }
}
