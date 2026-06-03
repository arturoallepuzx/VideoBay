<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\DomainDateTime;

final class IssuedAuthentication
{
    private function __construct(
        private string $accessToken,
        private DomainDateTime $accessTokenExpiresAt,
        private string $refreshToken,
        private DomainDateTime $refreshTokenExpiresAt,
    ) {
        if ($accessToken === '') {
            throw new \InvalidArgumentException('Access token cannot be empty.');
        }

        if ($refreshToken === '') {
            throw new \InvalidArgumentException('Refresh token cannot be empty.');
        }

        if ($refreshTokenExpiresAt->value() <= $accessTokenExpiresAt->value()) {
            throw new \InvalidArgumentException(
                'Refresh token expiresAt must be after access token expiresAt.'
            );
        }
    }

    public static function create(
        string $accessToken,
        DomainDateTime $accessTokenExpiresAt,
        string $refreshToken,
        DomainDateTime $refreshTokenExpiresAt,
    ): self {
        return new self(
            $accessToken,
            $accessTokenExpiresAt,
            $refreshToken,
            $refreshTokenExpiresAt,
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

    public function refreshToken(): string
    {
        return $this->refreshToken;
    }

    public function refreshTokenExpiresAt(): DomainDateTime
    {
        return $this->refreshTokenExpiresAt;
    }
}
