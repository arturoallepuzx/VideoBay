<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentMovieCredit extends Model
{
    protected $table = 'movie_credits';

    protected $fillable = [
        'movie_id',
        'person_id',
        'department',
        'job',
        'character_name',
        'credit_order',
    ];

    protected function casts(): array
    {
        return [
            'credit_order' => 'integer',
        ];
    }
}
