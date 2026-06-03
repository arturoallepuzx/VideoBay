<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentSubtitle extends Model
{
    use SoftDeletes;

    protected $table = 'subtitles';

    protected $fillable = [
        'uuid',
        'movie_id',
        'language',
        'label',
        'source',
        'provider',
        'external_id',
        'file_path',
        'original_format',
        'uploaded_by_user_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
