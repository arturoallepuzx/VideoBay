<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Services;

use App\Auth\Domain\Interfaces\RefreshTokenRepositoryInterface;
use App\Auth\Domain\ValueObject\RefreshTokenSecret;
use App\User\Domain\Interfaces\UserAuthenticationRevokerInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class JwtUserAuthenticationRevoker implements UserAuthenticationRevokerInterface
{
    public function __construct(
        private RefreshTokenRepositoryInterface $refreshTokenRepository,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function revoke(string $refreshCredential): void
    {
        try {
            $secret = RefreshTokenSecret::create($refreshCredential);
        } catch (\InvalidArgumentException) {
            return;
        }

        $token = $this->refreshTokenRepository->findByTokenHash($secret->hash());

        if ($token === null) {
            return;
        }

        $user = $this->userRepository->findById($token->userId());

        if ($user === null) {
            return;
        }

        $this->refreshTokenRepository->revokeAllInSession($token->sessionId());
    }
}
