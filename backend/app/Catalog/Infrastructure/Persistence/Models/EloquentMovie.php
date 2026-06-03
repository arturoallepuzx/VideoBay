<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentMovie extends Model
{
    use SoftDeletes;

    protected $table = 'movies';

    protected $fillable = [
        'uuid',
        'tmdb_id',
        'imdb_id',
        'title',
        'original_title',
        'overview',
        'release_date',
        'runtime_minutes',
        'original_language',
        'poster_path',
        'backdrop_path',
        'tmdb_rating',
        'cached_at',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'release_date' => 'immutable_date',
            'cached_at' => 'immutable_datetime',
            'tmdb_rating' => 'float',
            'runtime_minutes' => 'integer',
            'tmdb_id' => 'integer',
        ];
    }
}
