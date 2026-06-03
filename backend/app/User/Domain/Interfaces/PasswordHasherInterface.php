<?php

declare(strict_types=1);

namespace App\User\Domain\Interfaces;

use App\User\Domain\ValueObject\PasswordHash;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): PasswordHash;

    public function verify(string $plainPassword, PasswordHash $passwordHash): bool;
}
