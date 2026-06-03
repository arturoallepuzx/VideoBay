<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Services;

use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\Interfaces\RefreshTokenIssuerInterface;
use App\Auth\Domain\ValueObject\IssuedRefreshToken;
use App\Auth\Domain\ValueObject\RefreshTokenSecret;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class RandomRefreshTokenIssuer implements RefreshTokenIssuerInterface
{
    private const SECRET_BYTES = 32;

    public function issue(Uuid $userId, Uuid $sessionId, DomainDateTime $expiresAt): IssuedRefreshToken
    {
        $secret = RefreshTokenSecret::create($this->generateBase64UrlSecret());
        $entity = RefreshToken::dddCreate($userId, $sessionId, $secret, $expiresAt);

        return IssuedRefreshToken::create($entity, $secret);
    }

    private function generateBase64UrlSecret(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(self::SECRET_BYTES)), '+/', '-_'), '=');
    }
}
