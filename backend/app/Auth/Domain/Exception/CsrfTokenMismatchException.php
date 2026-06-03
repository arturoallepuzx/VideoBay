<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use App\Shared\Domain\Exception\ForbiddenException;

class CsrfTokenMismatchException extends ForbiddenException
{
    public static function missing(): self
    {
        return new self('CSRF token is missing.');
    }

    public static function mismatch(): self
    {
        return new self('CSRF token mismatch.');
    }
}
