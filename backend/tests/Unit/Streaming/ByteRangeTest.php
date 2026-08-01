<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Streaming\Domain\ValueObject\ByteRange;
use PHPUnit\Framework\TestCase;

class ByteRangeTest extends TestCase
{
    public function test_exposes_its_boundaries(): void
    {
        $range = ByteRange::create(500, 999);

        $this->assertSame(500, $range->start());
        $this->assertSame(999, $range->end());
    }

    public function test_length_counts_both_boundaries(): void
    {
        $this->assertSame(500, ByteRange::create(500, 999)->length());
        $this->assertSame(1, ByteRange::create(0, 0)->length());
    }

    public function test_equals_compares_boundaries(): void
    {
        $range = ByteRange::create(0, 99);

        $this->assertTrue($range->equals(ByteRange::create(0, 99)));
        $this->assertFalse($range->equals(ByteRange::create(0, 100)));
    }

    public function test_rejects_negative_start(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ByteRange::create(-1, 99);
    }

    public function test_rejects_end_before_start(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ByteRange::create(100, 99);
    }
}
