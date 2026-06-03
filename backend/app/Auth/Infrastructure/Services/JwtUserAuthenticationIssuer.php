<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Services;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\Interfaces\RefreshTokenIssuerInterface;
use App\Auth\Domain\Interfaces\RefreshTokenRepositoryInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserAuthenticationIssuerInterface;
use App\User\Domain\ValueObject\AuthenticationSubject;
use App\User\Domain\ValueObject\IssuedAuthentication;

class JwtUserAuthenticationIssuer implements UserAuthenticationIssuerInterface
{
    public function __construct(
        private AccessTokenIssuerInterface $accessTokenIssuer,
        private RefreshTokenIssuerInterface $refreshTokenIssuer,
        private RefreshTokenRepositoryInterface $refreshTokenRepository,
        private TransactionRunnerInterface $transactionRunner,
        private int $accessTtlSeconds,
        private int $refreshTtlSeconds,
        private int $maxConcurrentSessions,
    ) {
        if ($accessTtlSeconds <= 0) {
            throw new \InvalidArgumentException('Access token TTL must be greater than 0.');
        }

        if ($refreshTtlSeconds <= 0) {
            throw new \InvalidArgumentException('Refresh token TTL must be greater than 0.');
        }

        if ($refreshTtlSeconds <= $accessTtlSeconds) {
            throw new \InvalidArgumentException('Refresh token TTL must be greater than access token TTL.');
        }

        if ($maxConcurrentSessions <= 0) {
            throw new \InvalidArgumentException('Max concurrent sessions must be greater than 0.');
        }
    }

    public function issueFor(AuthenticationSubject $subject): IssuedAuthentication
    {
        $now = DomainDateTime::now();
        $sessionId = Uuid::generate();

        $accessExpiresAt = DomainDateTime::create(
            $now->value()->modify('+'.$this->accessTtlSeconds.' seconds')
        );

        $accessToken = $this->accessTokenIssuer->issue(
            AccessTokenPayload::create(
                $subject->userId(),
                $subject->role(),
                $sessionId,
                $now,
                $accessExpiresAt,
            )
        );

        $refreshExpiresAt = DomainDateTime::create(
            $now->value()->modify('+'.$this->refreshTtlSeconds.' seconds')
        );

        $issuedRefreshToken = $this->refreshTokenIssuer->issue(
            $subject->userId(),
            $sessionId,
            $refreshExpiresAt,
        );

        $this->transactionRunner->run(function () use ($issuedRefreshToken, $subject): void {
            $this->refreshTokenRepository->create($issuedRefreshToken->entity());
            $this->enforceConcurrentSessionsLimit($subject->userId());
        });

        return IssuedAuthentication::create(
            $accessToken->value(),
            $accessToken->expiresAt(),
            $issuedRefreshToken->secret()->value(),
            $issuedRefreshToken->entity()->expiresAt(),
        );
    }

    private function enforceConcurrentSessionsLimit(Uuid $userId): void
    {
        $activeSessions = $this->refreshTokenRepository->countActiveSessionsByUserId($userId);
        $excess = $activeSessions - $this->maxConcurrentSessions;

        if ($excess <= 0) {
            return;
        }

        $this->refreshTokenRepository->revokeOldestActiveSessionsByUserId($userId, $excess);
    }
}
