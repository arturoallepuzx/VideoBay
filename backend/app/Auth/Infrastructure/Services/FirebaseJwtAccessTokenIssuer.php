<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Services;

use App\Auth\Domain\Interfaces\AccessTokenIssuerInterface;
use App\Auth\Domain\ValueObject\AccessToken;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use Firebase\JWT\JWT;

class FirebaseJwtAccessTokenIssuer implements AccessTokenIssuerInterface
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private string $secret,
    ) {
        if ($secret === '') {
            throw new \InvalidArgumentException('JWT secret cannot be empty.');
        }
    }

    public function issue(AccessTokenPayload $payload): AccessToken
    {
        $token = JWT::encode(
            [
                'sub' => $payload->userId()->value(),
                'role' => $payload->role()->value(),
                'session_id' => $payload->sessionId()->value(),
                'iat' => $payload->issuedAt()->value()->getTimestamp(),
                'exp' => $payload->expiresAt()->value()->getTimestamp(),
            ],
            $this->secret,
            self::ALGORITHM,
        );

        return AccessToken::create($token, $payload->expiresAt());
    }
}
