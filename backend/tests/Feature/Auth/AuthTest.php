<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\Infrastructure\Http\Middleware\AuthenticateAccessToken;
use App\Auth\Infrastructure\Http\Middleware\EnsureXsrfCookie;
use App\Auth\Infrastructure\Http\Middleware\VerifyCsrfTokenStateless;
use App\Shared\Domain\ValueObject\AuthContext;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use InteractsWithAuth;
    use RefreshDatabase;

    public function test_login_with_valid_credentials_returns_200_and_sets_cookies(): void
    {
        $this->withoutCsrfMiddleware();
        $user = UserFactory::new()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertCookie('access_token');
        $response->assertCookie('refresh_token');
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        $this->withoutCsrfMiddleware();
        $user = UserFactory::new()->create();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_protected_route_without_token_returns_401(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_mutating_request_without_csrf_token_returns_403(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'someone@example.test',
            'password' => 'password',
        ])->assertStatus(403);
    }

    public function test_admin_route_as_customer_returns_403(): void
    {
        $holder = new AuthContextHolder;
        $holder->bind(AuthContext::create(Uuid::generate(), UserRole::customer(), Uuid::generate()));
        $this->app->instance(AuthContextHolder::class, $holder);
        $this->withoutMiddleware([
            AuthenticateAccessToken::class,
            EnsureXsrfCookie::class,
            VerifyCsrfTokenStateless::class,
        ]);

        $this->getJson('/api/users')->assertStatus(403);
    }
}
