<?php

declare(strict_types=1);

namespace App\User\Domain\Interfaces;

interface UserAuthenticationRevokerInterface
{
    public function revoke(string $refreshCredential): void;
}
