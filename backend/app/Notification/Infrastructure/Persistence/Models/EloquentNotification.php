<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentNotification extends Model
{
    public $timestamps = false;

    protected $table = 'notifications';

    protected $fillable = [
        'uuid',
        'user_id',
        'type',
        'title',
        'body',
        'action_url',
        'metadata',
        'read_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'read_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
