<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Auth\Infrastructure\Http\Middleware\AuthenticateAccessToken;
use App\Auth\Infrastructure\Http\Middleware\EnsureXsrfCookie;
use App\Auth\Infrastructure\Http\Middleware\RequireRole;
use App\Auth\Infrastructure\Http\Middleware\VerifyCsrfTokenStateless;
use App\Shared\Domain\ValueObject\AuthContext;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Auth\AuthContextHolder;

trait InteractsWithAuth
{
    protected function withoutAuthMiddleware(): static
    {
        return $this->withoutMiddleware([
            AuthenticateAccessToken::class,
            RequireRole::class,
            EnsureXsrfCookie::class,
            VerifyCsrfTokenStateless::class,
        ]);
    }

    protected function withoutCsrfMiddleware(): static
    {
        return $this->withoutMiddleware([
            EnsureXsrfCookie::class,
            VerifyCsrfTokenStateless::class,
        ]);
    }

    protected function actingAsContext(?string $userId = null, string $role = 'customer'): string
    {
        $userId ??= Uuid::generate()->value();

        $holder = new AuthContextHolder;
        $holder->bind(
            AuthContext::create(
                Uuid::create($userId),
                UserRole::create($role),
                Uuid::generate(),
            )
        );

        $this->app->instance(AuthContextHolder::class, $holder);
        $this->withoutAuthMiddleware();

        return $userId;
    }

    protected function actingAsAdmin(?string $userId = null): string
    {
        return $this->actingAsContext($userId, 'admin');
    }
}
