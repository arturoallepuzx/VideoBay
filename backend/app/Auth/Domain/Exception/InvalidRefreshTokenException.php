<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

class InvalidRefreshTokenException extends UnauthorizedException
{
    public static function notFound(): self
    {
        return new self('Refresh token is invalid.');
    }
}
