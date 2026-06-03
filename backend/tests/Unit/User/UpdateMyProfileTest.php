<?php

declare(strict_types=1);

namespace Tests\Unit\User;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\UpdateMyProfile\UpdateMyProfile;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserEmailAlreadyExistsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;
use Mockery;
use Tests\TestCase;

class UpdateMyProfileTest extends TestCase
{
    private const HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function user(): User
    {
        return User::dddCreate(
            UserRole::create('customer'),
            UserName::create('Test User'),
            Email::create('user@example.com'),
            PasswordHash::create(self::HASH),
        );
    }

    public function test_updates_name(): void
    {
        $user = $this->user();

        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->andReturn($user);
        $repository->shouldReceive('update')->once()->with($user);

        (new UpdateMyProfile($repository))($user->id()->value(), 'New Name', null, null, false);

        $this->assertSame('New Name', $user->name()->value());
    }

    public function test_updates_email_when_unique(): void
    {
        $user = $this->user();

        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->andReturn($user);
        $repository->shouldReceive('existsByEmailExcludingId')->once()->andReturn(false);
        $repository->shouldReceive('update')->once()->with($user);

        (new UpdateMyProfile($repository))($user->id()->value(), null, 'new@example.com', null, false);

        $this->assertSame('new@example.com', $user->email()->value());
    }

    public function test_throws_when_email_already_taken(): void
    {
        $user = $this->user();

        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->andReturn($user);
        $repository->shouldReceive('existsByEmailExcludingId')->once()->andReturn(true);
        $repository->shouldNotReceive('update');

        $this->expectException(UserEmailAlreadyExistsException::class);

        (new UpdateMyProfile($repository))($user->id()->value(), null, 'taken@example.com', null, false);
    }

    public function test_throws_when_user_not_found(): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->andReturn(null);
        $repository->shouldNotReceive('update');

        $this->expectException(UserNotFoundException::class);

        (new UpdateMyProfile($repository))(Uuid::generate()->value(), 'X', null, null, false);
    }
}
