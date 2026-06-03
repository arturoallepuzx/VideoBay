<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentPhysicalCopy extends Model
{
    use SoftDeletes;

    protected $table = 'physical_copies';

    protected $fillable = [
        'uuid',
        'movie_id',
        'sku',
        'barcode',
        'format',
        'region',
        'condition',
        'cover_photo_url',
        'price_cents',
        'stock_available',
        'stock_reserved',
        'active',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'stock_available' => 'integer',
            'stock_reserved' => 'integer',
            'active' => 'boolean',
        ];
    }
}
