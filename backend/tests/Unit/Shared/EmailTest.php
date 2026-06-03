<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function test_creates_from_valid_email(): void
    {
        $email = Email::create('user@example.com');

        $this->assertSame('user@example.com', $email->value());
    }

    public function test_throws_on_invalid_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Email::create('not-an-email');
    }

    public function test_equals_compares_by_value(): void
    {
        $this->assertTrue(Email::create('a@b.com')->equals(Email::create('a@b.com')));
        $this->assertFalse(Email::create('a@b.com')->equals(Email::create('c@d.com')));
    }
}
