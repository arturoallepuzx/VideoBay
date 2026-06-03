<?php

declare(strict_types=1);

namespace Tests\Unit\User;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\DeleteMyAccount\DeleteMyAccount;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserAuthenticationGlobalRevokerInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;
use Closure;
use Mockery;
use Tests\TestCase;

class DeleteMyAccountTest extends TestCase
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

    private function transactionRunner(): TransactionRunnerInterface
    {
        $runner = Mockery::mock(TransactionRunnerInterface::class);
        $runner->shouldReceive('run')->andReturnUsing(fn (Closure $callback) => $callback());

        return $runner;
    }

    public function test_anonymizes_soft_deletes_and_revokes_sessions(): void
    {
        $user = $this->user();

        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->andReturn($user);
        $repository->shouldReceive('update')->once()->with($user);
        $repository->shouldReceive('delete')->once();

        $revoker = Mockery::mock(UserAuthenticationGlobalRevokerInterface::class);
        $revoker->shouldReceive('revokeAllByUserId')->once();

        (new DeleteMyAccount($repository, $revoker, $this->transactionRunner()))($user->id()->value());

        $this->assertNotSame('user@example.com', $user->email()->value());
    }

    public function test_throws_when_user_not_found(): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->andReturn(null);
        $repository->shouldNotReceive('update');
        $repository->shouldNotReceive('delete');

        $revoker = Mockery::mock(UserAuthenticationGlobalRevokerInterface::class);
        $revoker->shouldNotReceive('revokeAllByUserId');

        $this->expectException(UserNotFoundException::class);

        (new DeleteMyAccount($repository, $revoker, $this->transactionRunner()))(Uuid::generate()->value());
    }
}
