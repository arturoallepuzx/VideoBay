<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentGenre extends Model
{
    public $timestamps = false;

    protected $table = 'genres';

    protected $fillable = [
        'tmdb_id',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'tmdb_id' => 'integer',
        ];
    }
}
