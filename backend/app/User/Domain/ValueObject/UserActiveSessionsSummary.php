<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;

final class UserActiveSessionsSummary
{
    private function __construct(
        private Uuid $userId,
        private UserName $name,
        private Email $email,
        private ?DomainDateTime $emailVerifiedAt,
        private UserRole $role,
        private ?AvatarUrl $avatarUrl,
        private int $activeSessions,
        private DomainDateTime $lastSeenAt,
    ) {
        if ($activeSessions <= 0) {
            throw new \InvalidArgumentException('Active sessions count must be greater than 0.');
        }
    }

    public static function create(
        Uuid $userId,
        UserName $name,
        Email $email,
        ?DomainDateTime $emailVerifiedAt,
        UserRole $role,
        ?AvatarUrl $avatarUrl,
        int $activeSessions,
        DomainDateTime $lastSeenAt,
    ): self {
        return new self($userId, $name, $email, $emailVerifiedAt, $role, $avatarUrl, $activeSessions, $lastSeenAt);
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function name(): UserName
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function emailVerifiedAt(): ?DomainDateTime
    {
        return $this->emailVerifiedAt;
    }

    public function role(): UserRole
    {
        return $this->role;
    }

    public function avatarUrl(): ?AvatarUrl
    {
        return $this->avatarUrl;
    }

    public function activeSessions(): int
    {
        return $this->activeSessions;
    }

    public function lastSeenAt(): DomainDateTime
    {
        return $this->lastSeenAt;
    }
}
