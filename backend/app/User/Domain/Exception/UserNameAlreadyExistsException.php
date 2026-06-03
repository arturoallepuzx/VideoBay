<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;

class UserNameAlreadyExistsException extends ConflictException
{
    public static function forName(string $name): self
    {
        return new self(sprintf('A user with name "%s" already exists.', $name));
    }
}
