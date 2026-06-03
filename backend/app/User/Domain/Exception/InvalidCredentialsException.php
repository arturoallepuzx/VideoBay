<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

class InvalidCredentialsException extends UnauthorizedException
{
    public static function invalid(): self
    {
        return new self('Invalid credentials.');
    }
}
