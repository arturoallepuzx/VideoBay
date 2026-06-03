<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentPerson extends Model
{
    use SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'uuid',
        'tmdb_id',
        'name',
        'biography',
        'profile_path',
        'birthday',
        'deathday',
        'place_of_birth',
        'cached_at',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'immutable_date',
            'deathday' => 'immutable_date',
            'cached_at' => 'immutable_datetime',
            'tmdb_id' => 'integer',
        ];
    }
}
