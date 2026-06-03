<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class UserNotFoundException extends NotFoundException
{
    public static function forId(Uuid $userId): self
    {
        return new self(
            sprintf('User "%s" not found.', $userId->value())
        );
    }
}
