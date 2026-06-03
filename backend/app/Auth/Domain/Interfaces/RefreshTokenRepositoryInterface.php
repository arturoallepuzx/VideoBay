<?php

declare(strict_types=1);

namespace App\Auth\Domain\Interfaces;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\ValueObject\RefreshTokenHash;
use App\Shared\Domain\ValueObject\Uuid;

interface RefreshTokenRepositoryInterface
{
    public function create(RefreshToken $refreshToken): void;

    public function findByTokenHash(RefreshTokenHash $tokenHash): ?RefreshToken;

    public function update(RefreshToken $refreshToken): void;

    public function revokeAllInSession(Uuid $sessionId): void;

    public function revokeAllByUserId(Uuid $userId): void;

    public function countActiveSessionsByUserId(Uuid $userId): int;

    public function revokeOldestActiveSessionsByUserId(Uuid $userId, int $howMany): void;
}
