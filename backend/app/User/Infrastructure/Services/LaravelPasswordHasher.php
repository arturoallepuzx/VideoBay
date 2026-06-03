<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Services;

use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\ValueObject\PasswordHash;
use Illuminate\Support\Facades\Hash;

class LaravelPasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): PasswordHash
    {
        return PasswordHash::create(Hash::make($plainPassword));
    }

    public function verify(string $plainPassword, PasswordHash $passwordHash): bool
    {
        return Hash::check($plainPassword, $passwordHash->value());
    }
}
