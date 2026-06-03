<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentSubtitleReport extends Model
{
    protected $table = 'subtitle_reports';

    protected $fillable = [
        'subtitle_id',
        'reported_by_user_id',
        'reason',
        'status',
        'created_at',
        'updated_at',
    ];
}
