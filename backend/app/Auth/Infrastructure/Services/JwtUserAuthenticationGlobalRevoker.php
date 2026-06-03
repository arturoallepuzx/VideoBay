<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Services;

use App\Auth\Domain\Interfaces\RefreshTokenRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserAuthenticationGlobalRevokerInterface;

class JwtUserAuthenticationGlobalRevoker implements UserAuthenticationGlobalRevokerInterface
{
    public function __construct(
        private RefreshTokenRepositoryInterface $refreshTokenRepository,
    ) {}

    public function revokeAllByUserId(Uuid $userId): void
    {
        $this->refreshTokenRepository->revokeAllByUserId($userId);
    }
}
