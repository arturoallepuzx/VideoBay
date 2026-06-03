<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

class UserEmailAlreadyExistsException extends ConflictException
{
    public static function forEmail(string $email): self
    {
        return new self(sprintf('A user with email "%s" already exists.', $email));
    }
}
