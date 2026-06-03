<?php

declare(strict_types=1);

namespace App\User\Application\LoginUser;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\IssuedAuthentication;

final class LoginUserResponse
{
    /** @param array<string, mixed> $accessibilitySettings */
    private function __construct(
        private string $userId,
        private string $role,
        private string $name,
        private string $email,
        private ?string $emailVerifiedAt,
        private ?string $avatarUrl,
        private array $accessibilitySettings,
        private string $accessToken,
        private DomainDateTime $accessTokenExpiresAt,
        private string $refreshToken,
        private DomainDateTime $refreshTokenExpiresAt,
    ) {}

    public static function create(
        User $user,
        IssuedAuthentication $issuedAuthentication,
    ): self {
        return new self(
            userId: $user->id()->value(),
            role: $user->role()->value(),
            name: $user->name()->value(),
            email: $user->email()->value(),
            emailVerifiedAt: $user->emailVerifiedAt()?->format(\DateTimeInterface::ATOM),
            avatarUrl: $user->avatarUrl()?->value(),
            accessibilitySettings: $user->accessibilitySettings()->toArray(),
            accessToken: $issuedAuthentication->accessToken(),
            accessTokenExpiresAt: $issuedAuthentication->accessTokenExpiresAt(),
            refreshToken: $issuedAuthentication->refreshToken(),
            refreshTokenExpiresAt: $issuedAuthentication->refreshTokenExpiresAt(),
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

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'user' => [
                'id' => $this->userId,
                'role' => $this->role,
                'name' => $this->name,
                'email' => $this->email,
                'email_verified_at' => $this->emailVerifiedAt,
                'avatar_url' => $this->avatarUrl,
                'accessibility_settings' => $this->accessibilitySettings,
            ],
        ];
    }
}
