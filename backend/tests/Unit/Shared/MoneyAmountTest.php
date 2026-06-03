<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Domain\ValueObject\MoneyAmount;
use PHPUnit\Framework\TestCase;

class MoneyAmountTest extends TestCase
{
    public function test_creates_with_cents_and_currency(): void
    {
        $money = MoneyAmount::create(1500, 'EUR');

        $this->assertSame(1500, $money->cents());
        $this->assertSame('EUR', $money->currency());
    }

    public function test_throws_on_negative_cents(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        MoneyAmount::create(-1, 'EUR');
    }

    public function test_throws_on_invalid_currency(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        MoneyAmount::create(100, 'eur');
    }

    public function test_multiply_scales_cents(): void
    {
        $money = MoneyAmount::create(1000, 'EUR')->multiply(0.85);

        $this->assertSame(850, $money->cents());
        $this->assertSame('EUR', $money->currency());
    }

    public function test_multiply_rejects_negative_factor(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        MoneyAmount::create(1000, 'EUR')->multiply(-1.0);
    }

    public function test_equals_compares_cents_and_currency(): void
    {
        $this->assertTrue(MoneyAmount::create(100, 'EUR')->equals(MoneyAmount::create(100, 'EUR')));
        $this->assertFalse(MoneyAmount::create(100, 'EUR')->equals(MoneyAmount::create(100, 'USD')));
        $this->assertFalse(MoneyAmount::create(100, 'EUR')->equals(MoneyAmount::create(200, 'EUR')));
    }
}
