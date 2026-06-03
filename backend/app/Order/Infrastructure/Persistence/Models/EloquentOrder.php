<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentOrder extends Model
{
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'uuid',
        'user_id',
        'status',
        'total_cents',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'pickup_code',
        'paid_at',
        'ready_at',
        'picked_up_at',
        'cancelled_at',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'total_cents' => 'integer',
            'paid_at' => 'immutable_datetime',
            'ready_at' => 'immutable_datetime',
            'picked_up_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<EloquentOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(EloquentOrderItem::class, 'order_id');
    }
}
