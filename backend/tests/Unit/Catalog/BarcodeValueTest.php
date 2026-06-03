<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Shared\Domain\ValueObject\BarcodeValue;
use PHPUnit\Framework\TestCase;

class BarcodeValueTest extends TestCase
{
    public function test_create_accepts_valid_barcode(): void
    {
        $barcode = BarcodeValue::create('8412345678901');

        $this->assertSame('8412345678901', $barcode->value());
    }

    public function test_create_trims_whitespace(): void
    {
        $barcode = BarcodeValue::create('  8412345678901  ');

        $this->assertSame('8412345678901', $barcode->value());
    }

    public function test_create_rejects_too_short_barcode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        BarcodeValue::create('1234567');
    }

    public function test_create_rejects_too_long_barcode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        BarcodeValue::create(str_repeat('1', 33));
    }

    public function test_create_rejects_non_numeric_barcode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        BarcodeValue::create('8412345abcdef');
    }

    public function test_equals_returns_true_for_same_value(): void
    {
        $a = BarcodeValue::create('8412345678901');
        $b = BarcodeValue::create('8412345678901');

        $this->assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_value(): void
    {
        $a = BarcodeValue::create('8412345678901');
        $b = BarcodeValue::create('8412345678902');

        $this->assertFalse($a->equals($b));
    }
}
