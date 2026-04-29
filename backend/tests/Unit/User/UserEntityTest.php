<?php

namespace Tests\Unit\User;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;
use PHPUnit\Framework\TestCase;

class UserEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_with_attributes_and_vos(): void
    {
        $email = Email::create('user@example.com');
        $nameVO = UserName::create('Test User');
        $passwordHashVO = PasswordHash::create(
            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
        );

        $user = User::dddCreate($email, $nameVO, $passwordHashVO);

        $this->assertInstanceOf(User::class, $user);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $user->id()->value()
        );
        $this->assertSame('Test User', $user->name());
        $this->assertSame('user@example.com', $user->email()->value());
        $this->assertSame(
            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            $user->passwordHash()
        );
        $this->assertInstanceOf(DomainDateTime::class, $user->createdAt());
        $this->assertInstanceOf(DomainDateTime::class, $user->updatedAt());
        $this->assertEquals($user->createdAt()->value()->getTimestamp(), $user->updatedAt()->value()->getTimestamp(), 1);
    }
}
