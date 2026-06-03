<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

class InvalidAccessTokenException extends UnauthorizedException
{
    public static function malformed(): self
    {
        return new self('Access token is malformed or signature is invalid.');
    }
}
