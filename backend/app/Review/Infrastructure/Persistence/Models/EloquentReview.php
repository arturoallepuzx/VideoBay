<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentReview extends Model
{
    use SoftDeletes;

    protected $table = 'reviews';

    protected $fillable = [
        'uuid',
        'user_id',
        'movie_id',
        'rating',
        'body',
        'contains_spoilers',
        'likes_count',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'contains_spoilers' => 'boolean',
            'likes_count' => 'integer',
        ];
    }
}
