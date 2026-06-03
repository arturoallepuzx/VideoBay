<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class EloquentRefreshToken extends Model
{
    protected $table = 'refresh_tokens';

    protected $fillable = [
        'uuid',
        'session_uuid',
        'user_id',
        'token_hash',
        'expires_at',
        'revoked_at',
        'replaced_by_id',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
