<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSaleProposal extends Model
{
    use SoftDeletes;

    protected $table = 'sale_proposals';

    protected $fillable = [
        'uuid',
        'user_id',
        'movie_id',
        'title_text',
        'barcode',
        'format',
        'condition',
        'notes',
        'offered_price_cents',
        'status',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'offered_price_cents' => 'integer',
        ];
    }
}
