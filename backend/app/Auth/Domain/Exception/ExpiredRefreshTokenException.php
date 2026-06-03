<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;
use App\Shared\Domain\ValueObject\DomainDateTime;

class ExpiredRefreshTokenException extends UnauthorizedException
{
    public static function expiredAt(DomainDateTime $expiredAt): self
    {
        return new self(
            sprintf('Refresh token expired at %s.', $expiredAt->format(\DateTimeInterface::ATOM))
        );
    }
}
