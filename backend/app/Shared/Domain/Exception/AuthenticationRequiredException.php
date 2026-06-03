<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

class AuthenticationRequiredException extends UnauthorizedException
{
    public static function missing(): self
    {
        return new self('Authentication is required.');
    }

    public static function invalid(): self
    {
        return new self('Authentication is invalid.');
    }
}
