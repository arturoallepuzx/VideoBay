<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Services;

use App\Auth\Domain\Exception\ExpiredAccessTokenException;
use App\Auth\Domain\Exception\InvalidAccessTokenException;
use App\Auth\Domain\Interfaces\AccessTokenVerifierInterface;
use App\Auth\Domain\ValueObject\AccessTokenPayload;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class FirebaseJwtAccessTokenVerifier implements AccessTokenVerifierInterface
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private string $secret,
    ) {
        if ($secret === '') {
            throw new \InvalidArgumentException('JWT secret cannot be empty.');
        }
    }

    public function verify(string $token): AccessTokenPayload
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
        } catch (ExpiredException $e) {
            $expiredAt = $this->tryReadExpFromToken($token);
            throw $expiredAt !== null
                ? ExpiredAccessTokenException::expiredAt($expiredAt)
                : ExpiredAccessTokenException::expired();
        } catch (\Throwable $e) {
            throw InvalidAccessTokenException::malformed();
        }

        try {
            return AccessTokenPayload::create(
                Uuid::create((string) $decoded->sub),
                UserRole::create((string) $decoded->role),
                Uuid::create((string) $decoded->session_id),
                DomainDateTime::create(new \DateTimeImmutable('@'.(int) $decoded->iat)),
                DomainDateTime::create(new \DateTimeImmutable('@'.(int) $decoded->exp)),
            );
        } catch (\Throwable $e) {
            throw InvalidAccessTokenException::malformed();
        }
    }

    // Only enriches the expired-exception message. Security validation already happened in JWT::decode.
    private function tryReadExpFromToken(string $token): ?DomainDateTime
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($parts[1]);
        if ($payloadJson === null) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (! is_array($payload) || ! isset($payload['exp']) || ! is_int($payload['exp'])) {
            return null;
        }

        return DomainDateTime::create(new \DateTimeImmutable('@'.$payload['exp']));
    }

    private function base64UrlDecode(string $value): ?string
    {
        $remainder = strlen($value) % 4;
        if ($remainder !== 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
