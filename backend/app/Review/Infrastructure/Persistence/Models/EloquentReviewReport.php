<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentReviewReport extends Model
{
    protected $table = 'review_reports';

    protected $fillable = [
        'review_id',
        'reported_by_user_id',
        'reason',
        'status',
        'created_at',
        'updated_at',
    ];
}
