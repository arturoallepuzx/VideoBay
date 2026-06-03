<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;
use App\Shared\Domain\ValueObject\Uuid;

class RefreshTokenReuseDetectedException extends UnauthorizedException
{
    public static function forSession(Uuid $sessionId): self
    {
        return new self(
            sprintf('Refresh token reuse detected for session "%s".', $sessionId->value())
        );
    }
}
