<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EloquentCart extends Model
{
    protected $table = 'carts';

    protected $fillable = [
        'uuid',
        'user_id',
        'created_at',
        'updated_at',
    ];

    /**
     * @return HasMany<EloquentCartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(EloquentCartItem::class, 'cart_id');
    }
}
