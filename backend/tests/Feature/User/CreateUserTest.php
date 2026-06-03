<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Auth\Infrastructure\Http\Middleware\AuthenticateAccessToken;
use App\Auth\Infrastructure\Http\Middleware\EnsureXsrfCookie;
use App\Auth\Infrastructure\Http\Middleware\RequireRole;
use App\Auth\Infrastructure\Http\Middleware\VerifyCsrfTokenStateless;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_users_returns_201_and_user_json(): void
    {
        $response = $this
            ->withoutMiddleware([
                AuthenticateAccessToken::class,
                RequireRole::class,
                EnsureXsrfCookie::class,
                VerifyCsrfTokenStateless::class,
            ])
            ->postJson('/api/users', [
                'role' => 'customer',
                'name' => 'Integration User',
                'email' => 'integration@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'role',
            'name',
            'email',
            'email_verified_at',
            'avatar_url',
            'created_at',
            'updated_at',
        ]);
        $response->assertJson([
            'role' => 'customer',
            'name' => 'Integration User',
            'email' => 'integration@example.com',
            'email_verified_at' => null,
            'avatar_url' => null,
        ]);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $response->json('id')
        );
    }
}
