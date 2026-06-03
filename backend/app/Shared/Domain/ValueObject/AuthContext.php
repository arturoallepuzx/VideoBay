<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final class AuthContext
{
    private function __construct(
        private Uuid $userId,
        private UserRole $role,
        private Uuid $sessionId,
    ) {}

    public static function create(
        Uuid $userId,
        UserRole $role,
        Uuid $sessionId,
    ): self {
        return new self($userId, $role, $sessionId);
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function role(): UserRole
    {
        return $this->role;
    }

    public function sessionId(): Uuid
    {
        return $this->sessionId;
    }

    public function isAdmin(): bool
    {
        return $this->role->equals(UserRole::admin());
    }
}
