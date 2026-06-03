<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EloquentUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'email_verified_at',
        'password_hash',
        'avatar_url',
        'role',
        'accessibility_settings',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'accessibility_settings' => 'array',
        ];
    }

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
