<?php

declare(strict_types=1);

namespace App\User\Application\LogoutAllUserSessions;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserAuthenticationGlobalRevokerInterface;

class LogoutAllUserSessions
{
    public function __construct(
        private UserAuthenticationGlobalRevokerInterface $userAuthenticationGlobalRevoker,
    ) {}

    public function __invoke(string $userId): void
    {
        $this->userAuthenticationGlobalRevoker->revokeAllByUserId(Uuid::create($userId));
    }
}
