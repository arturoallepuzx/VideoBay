<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;

final class AuthenticationSubject
{
    private function __construct(
        private Uuid $userId,
        private UserRole $role,
    ) {}

    public static function create(
        Uuid $userId,
        UserRole $role,
    ): self {
        return new self($userId, $role);
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function role(): UserRole
    {
        return $this->role;
    }
}
