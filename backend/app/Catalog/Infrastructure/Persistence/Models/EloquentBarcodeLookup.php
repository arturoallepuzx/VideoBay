<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentBarcodeLookup extends Model
{
    protected $table = 'barcode_lookups';

    protected $primaryKey = 'barcode';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'barcode',
        'movie_id',
        'resolved_via',
        'confirmed',
    ];

    protected function casts(): array
    {
        return [
            'confirmed' => 'boolean',
        ];
    }
}
