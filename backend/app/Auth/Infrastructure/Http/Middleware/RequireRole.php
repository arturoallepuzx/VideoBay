<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Http\Middleware;

use App\Auth\Domain\Exception\RoleNotAllowedException;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function __construct(
        private AuthContextHolder $authContextHolder,
    ) {}

    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        if ($allowedRoles === []) {
            throw new \InvalidArgumentException('At least one role must be configured.');
        }

        foreach ($allowedRoles as $allowedRole) {
            UserRole::create($allowedRole);
        }

        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        if (! in_array($context->role()->value(), $allowedRoles, true)) {
            throw RoleNotAllowedException::forRole($context->role());
        }

        return $next($request);
    }
}
