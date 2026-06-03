<?php

declare(strict_types=1);

namespace App\User\Application\LogoutUser;

use App\User\Domain\Interfaces\UserAuthenticationRevokerInterface;

class LogoutUser
{
    public function __construct(
        private UserAuthenticationRevokerInterface $userAuthenticationRevoker,
    ) {}

    public function __invoke(string $refreshCredential): void
    {
        $this->userAuthenticationRevoker->revoke($refreshCredential);
    }
}
