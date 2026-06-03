<?php

declare(strict_types=1);

namespace App\User\Domain\Interfaces;

use App\User\Domain\ValueObject\IssuedAuthentication;

interface UserAuthenticationRefresherInterface
{
    public function refresh(string $refreshCredential): IssuedAuthentication;
}
