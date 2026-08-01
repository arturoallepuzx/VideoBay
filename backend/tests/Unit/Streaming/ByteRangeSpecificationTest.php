<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Streaming\Domain\Exception\InvalidVideoRangeException;
use App\Streaming\Domain\ValueObject\ByteRange;
use App\Streaming\Domain\ValueObject\ByteRangeSpecification;
use PHPUnit\Framework\TestCase;

class ByteRangeSpecificationTest extends TestCase
{
    public function test_resolves_a_closed_specification(): void
    {
        $range = ByteRangeSpecification::create(0, 499)->resolveAgainst(1000);

        $this->assertTrue(ByteRange::create(0, 499)->equals($range));
    }

    public function test_resolves_an_open_ended_specification_up_to_the_last_byte(): void
    {
        $range = ByteRangeSpecification::create(500, null)->resolveAgainst(1000);

        $this->assertTrue(ByteRange::create(500, 999)->equals($range));
    }

    public function test_clamps_a_last_byte_position_beyond_the_resource(): void
    {
        $range = ByteRangeSpecification::create(0, 5000)->resolveAgainst(1000);

        $this->assertTrue(ByteRange::create(0, 999)->equals($range));
    }

    public function test_resolves_a_suffix_specification_as_the_trailing_bytes(): void
    {
        $range = ByteRangeSpecification::fromSuffixLength(100)->resolveAgainst(1000);

        $this->assertTrue(ByteRange::create(900, 999)->equals($range));
    }

    public function test_clamps_a_suffix_longer_than_the_resource(): void
    {
        $range = ByteRangeSpecification::fromSuffixLength(5000)->resolveAgainst(1000);

        $this->assertTrue(ByteRange::create(0, 999)->equals($range));
    }

    public function test_rejects_a_first_byte_position_beyond_the_resource(): void
    {
        $this->expectException(InvalidVideoRangeException::class);

        ByteRangeSpecification::create(1000, null)->resolveAgainst(1000);
    }

    public function test_rejects_a_closed_specification_beyond_the_resource(): void
    {
        $this->expectException(InvalidVideoRangeException::class);

        ByteRangeSpecification::create(1500, 2000)->resolveAgainst(1000);
    }

    public function test_rejects_an_empty_suffix(): void
    {
        $this->expectException(InvalidVideoRangeException::class);

        ByteRangeSpecification::fromSuffixLength(0)->resolveAgainst(1000);
    }

    public function test_rejects_resolving_against_an_empty_resource(): void
    {
        $this->expectException(InvalidVideoRangeException::class);

        ByteRangeSpecification::create(0, null)->resolveAgainst(0);
    }

    public function test_rejects_a_negative_first_byte_position(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ByteRangeSpecification::create(-1, null);
    }

    public function test_rejects_a_last_byte_position_before_the_first(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ByteRangeSpecification::create(500, 400);
    }

    public function test_equals_compares_every_boundary(): void
    {
        $specification = ByteRangeSpecification::create(0, 99);

        $this->assertTrue($specification->equals(ByteRangeSpecification::create(0, 99)));
        $this->assertFalse($specification->equals(ByteRangeSpecification::create(0, null)));
        $this->assertFalse($specification->equals(ByteRangeSpecification::fromSuffixLength(99)));
    }
}
