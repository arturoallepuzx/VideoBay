<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Auth;

use App\Shared\Domain\ValueObject\AuthContext;

class AuthContextHolder
{
    private ?AuthContext $context = null;

    public function bind(AuthContext $context): void
    {
        $this->context = $context;
    }

    public function get(): ?AuthContext
    {
        return $this->context;
    }
}
