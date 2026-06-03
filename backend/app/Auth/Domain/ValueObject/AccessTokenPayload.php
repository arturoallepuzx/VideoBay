<?php

declare(strict_types=1);

namespace App\Auth\Domain\ValueObject;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;

class AccessTokenPayload
{
    private Uuid $userId;

    private UserRole $role;

    private Uuid $sessionId;

    private DomainDateTime $issuedAt;

    private DomainDateTime $expiresAt;

    private function __construct(
        Uuid $userId,
        UserRole $role,
        Uuid $sessionId,
        DomainDateTime $issuedAt,
        DomainDateTime $expiresAt,
    ) {
        if ($expiresAt->value() <= $issuedAt->value()) {
            throw new \InvalidArgumentException('Access token expiresAt must be after issuedAt.');
        }

        $this->userId = $userId;
        $this->role = $role;
        $this->sessionId = $sessionId;
        $this->issuedAt = $issuedAt;
        $this->expiresAt = $expiresAt;
    }

    public static function create(
        Uuid $userId,
        UserRole $role,
        Uuid $sessionId,
        DomainDateTime $issuedAt,
        DomainDateTime $expiresAt,
    ): self {
        return new self($userId, $role, $sessionId, $issuedAt, $expiresAt);
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

    public function issuedAt(): DomainDateTime
    {
        return $this->issuedAt;
    }

    public function expiresAt(): DomainDateTime
    {
        return $this->expiresAt;
    }
}
