<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Domain\ValueObject\UserRole;

class RoleNotAllowedException extends ForbiddenException
{
    public static function forRole(UserRole $role): self
    {
        return new self(sprintf('Role "%s" is not allowed for this resource.', $role->value()));
    }
}
