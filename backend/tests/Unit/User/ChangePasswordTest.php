<?php

declare(strict_types=1);

namespace Tests\Unit\User;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\ChangePassword\ChangePassword;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserAuthenticationGlobalRevokerInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;
use Closure;
use Mockery;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    private const HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    private const NEW_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igj';

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

    private function transactionRunner(): TransactionRunnerInterface
    {
        $runner = Mockery::mock(TransactionRunnerInterface::class);
        $runner->shouldReceive('run')->andReturnUsing(fn (Closure $callback) => $callback());

        return $runner;
    }

    public function test_changes_password_and_revokes_all_sessions(): void
    {
        $user = $this->user();

        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->andReturn($user);
        $repository->shouldReceive('update')->once()->with($user);

        $hasher = Mockery::mock(PasswordHasherInterface::class);
        $hasher->shouldReceive('verify')->once()->andReturn(true);
        $hasher->shouldReceive('hash')->once()->with('new-password')->andReturn(PasswordHash::create(self::NEW_HASH));

        $revoker = Mockery::mock(UserAuthenticationGlobalRevokerInterface::class);
        $revoker->shouldReceive('revokeAllByUserId')->once();

        (new ChangePassword($repository, $hasher, $revoker, $this->transactionRunner()))(
            $user->id()->value(),
            'current-password',
            'new-password',
        );

        $this->assertSame(self::NEW_HASH, $user->passwordHash()->value());
    }

    public function test_throws_when_current_password_is_wrong(): void
    {
        $user = $this->user();

        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->andReturn($user);
        $repository->shouldNotReceive('update');

        $hasher = Mockery::mock(PasswordHasherInterface::class);
        $hasher->shouldReceive('verify')->once()->andReturn(false);
        $hasher->shouldNotReceive('hash');

        $revoker = Mockery::mock(UserAuthenticationGlobalRevokerInterface::class);
        $revoker->shouldNotReceive('revokeAllByUserId');

        $this->expectException(InvalidCredentialsException::class);

        (new ChangePassword($repository, $hasher, $revoker, $this->transactionRunner()))(
            $user->id()->value(),
            'wrong-password',
            'new-password',
        );
    }

    public function test_throws_when_user_not_found(): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->andReturn(null);
        $repository->shouldNotReceive('update');

        $hasher = Mockery::mock(PasswordHasherInterface::class);
        $revoker = Mockery::mock(UserAuthenticationGlobalRevokerInterface::class);

        $this->expectException(UserNotFoundException::class);

        (new ChangePassword($repository, $hasher, $revoker, $this->transactionRunner()))(
            Uuid::generate()->value(),
            'current-password',
            'new-password',
        );
    }
}
