<?php

declare(strict_types=1);

namespace App\User\Application\RefreshAuthentication;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\User\Domain\ValueObject\IssuedAuthentication;

final class RefreshAuthenticationResponse
{
    private function __construct(
        private string $accessToken,
        private DomainDateTime $accessTokenExpiresAt,
        private string $refreshCredential,
        private DomainDateTime $refreshCredentialExpiresAt,
    ) {}

    public static function create(IssuedAuthentication $issuedAuthentication): self
    {
        return new self(
            accessToken: $issuedAuthentication->accessToken(),
            accessTokenExpiresAt: $issuedAuthentication->accessTokenExpiresAt(),
            refreshCredential: $issuedAuthentication->refreshToken(),
            refreshCredentialExpiresAt: $issuedAuthentication->refreshTokenExpiresAt(),
        );
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function accessTokenExpiresAt(): DomainDateTime
    {
        return $this->accessTokenExpiresAt;
    }

    public function refreshCredential(): string
    {
        return $this->refreshCredential;
    }

    public function refreshCredentialExpiresAt(): DomainDateTime
    {
        return $this->refreshCredentialExpiresAt;
    }
}
