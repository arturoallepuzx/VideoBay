<?php

declare(strict_types=1);

namespace Tests\Unit\User;

use App\Shared\Domain\ValueObject\Email;
use App\User\Application\CreateUser\CreateUser;
use App\User\Application\CreateUser\CreateUserResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\PasswordHash;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateUserTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_creates_user_persists_via_repository_and_returns_response(): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $passwordHasher = Mockery::mock(PasswordHasherInterface::class);

        $hashedPasswordHash = PasswordHash::create(
            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
        );

        $repository->shouldReceive('existsByEmail')
            ->once()
            ->with(Mockery::on(fn (Email $email): bool => $email->value() === 'create@example.com'))
            ->andReturn(false);

        $passwordHasher->shouldReceive('hash')
            ->once()
            ->with('plain-password')
            ->andReturn($hashedPasswordHash);

        $repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (User $user) use ($hashedPasswordHash): bool {
                return $user->email()->value() === 'create@example.com'
                    && $user->name()->value() === 'Create User'
                    && $user->role()->value() === 'customer'
                    && $user->passwordHash()->value() === $hashedPasswordHash->value();
            }));

        $createUser = new CreateUser($repository, $passwordHasher);
        $response = $createUser('customer', 'Create User', 'create@example.com', 'plain-password');

        $this->assertInstanceOf(CreateUserResponse::class, $response);
        $this->assertSame('create@example.com', $response->email);
        $this->assertSame('Create User', $response->name);
        $this->assertSame('customer', $response->role);
        $this->assertNull($response->avatarUrl);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $response->id
        );

        $array = $response->toArray();
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
    }
}
