<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentVideoFile extends Model
{
    use SoftDeletes;

    protected $table = 'video_files';

    protected $fillable = [
        'uuid',
        'movie_id',
        'original_filename',
        'original_format',
        'original_path',
        'processed_path',
        'mime_type',
        'duration_seconds',
        'file_size_bytes',
        'audio_language',
        'processing_status',
        'processing_error',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'file_size_bytes' => 'integer',
        ];
    }
}
