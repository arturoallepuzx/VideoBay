<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentCartItem extends Model
{
    protected $table = 'cart_items';

    protected $fillable = [
        'cart_id',
        'physical_copy_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }
}
