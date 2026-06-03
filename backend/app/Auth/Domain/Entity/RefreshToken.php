<?php

declare(strict_types=1);

namespace App\Auth\Domain\Entity;

use App\Auth\Domain\ValueObject\RefreshTokenHash;
use App\Auth\Domain\ValueObject\RefreshTokenSecret;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class RefreshToken
{
    private function __construct(
        private Uuid $id,
        private Uuid $userId,
        private Uuid $sessionId,
        private RefreshTokenHash $tokenHash,
        private DomainDateTime $expiresAt,
        private ?DomainDateTime $revokedAt,
        private ?Uuid $replacedById,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $userId,
        Uuid $sessionId,
        RefreshTokenSecret $secret,
        DomainDateTime $expiresAt,
    ): self {
        $now = DomainDateTime::now();

        if ($expiresAt->value() <= $now->value()) {
            throw new \InvalidArgumentException('Refresh token expiresAt must be after creation time.');
        }

        return new self(
            Uuid::generate(),
            $userId,
            $sessionId,
            $secret->hash(),
            $expiresAt,
            null,
            null,
            $now,
            $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $userId,
        string $sessionId,
        string $tokenHash,
        \DateTimeImmutable $expiresAt,
        ?\DateTimeImmutable $revokedAt,
        ?string $replacedById,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            Uuid::create($userId),
            Uuid::create($sessionId),
            RefreshTokenHash::create($tokenHash),
            DomainDateTime::create($expiresAt),
            $revokedAt !== null ? DomainDateTime::create($revokedAt) : null,
            $replacedById !== null ? Uuid::create($replacedById) : null,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function isExpired(): bool
    {
        return $this->expiresAt->value() <= DomainDateTime::now()->value();
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function revoke(): void
    {
        if ($this->revokedAt !== null) {
            return;
        }
        $this->revokedAt = DomainDateTime::now();
        $this->touch();
    }

    public function markReplacedBy(Uuid $replacementId): void
    {
        $this->replacedById = $replacementId;
        if ($this->revokedAt === null) {
            $this->revokedAt = DomainDateTime::now();
        }
        $this->touch();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function sessionId(): Uuid
    {
        return $this->sessionId;
    }

    public function tokenHash(): RefreshTokenHash
    {
        return $this->tokenHash;
    }

    public function expiresAt(): DomainDateTime
    {
        return $this->expiresAt;
    }

    public function revokedAt(): ?DomainDateTime
    {
        return $this->revokedAt;
    }

    public function replacedById(): ?Uuid
    {
        return $this->replacedById;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = DomainDateTime::now();
    }
}
