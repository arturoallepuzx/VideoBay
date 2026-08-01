<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Streaming\Domain\ValueObject\ByteRangeSpecification;
use App\Streaming\Infrastructure\Services\RangeHeaderParser;
use PHPUnit\Framework\TestCase;

class RangeHeaderParserTest extends TestCase
{
    private RangeHeaderParser $parser;

    /** @return iterable<string, array{string}> */
    public static function ignoredHeaderProvider(): iterable
    {
        yield 'unsupported unit' => ['items=0-499'];
        yield 'multiple ranges' => ['bytes=0-99,200-299'];
        yield 'empty specification' => ['bytes='];
        yield 'missing separator' => ['bytes=500'];
        yield 'only separator' => ['bytes=-'];
        yield 'non numeric first position' => ['bytes=abc-499'];
        yield 'non numeric last position' => ['bytes=0-abc'];
        yield 'non numeric suffix' => ['bytes=-abc'];
        yield 'last position before first' => ['bytes=500-400'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new RangeHeaderParser;
    }

    public function test_reads_no_specification_when_the_header_is_absent(): void
    {
        $this->assertNull($this->parser->parse(null));
    }

    public function test_reads_a_closed_specification(): void
    {
        $specification = $this->parser->parse('bytes=0-499');

        $this->assertNotNull($specification);
        $this->assertTrue(ByteRangeSpecification::create(0, 499)->equals($specification));
    }

    public function test_reads_an_open_ended_specification(): void
    {
        $specification = $this->parser->parse('bytes=500-');

        $this->assertNotNull($specification);
        $this->assertTrue(ByteRangeSpecification::create(500, null)->equals($specification));
    }

    public function test_reads_a_suffix_specification(): void
    {
        $specification = $this->parser->parse('bytes=-100');

        $this->assertNotNull($specification);
        $this->assertTrue(ByteRangeSpecification::fromSuffixLength(100)->equals($specification));
    }

    public function test_reads_a_specification_surrounded_by_whitespace(): void
    {
        $specification = $this->parser->parse('  bytes=0-499  ');

        $this->assertNotNull($specification);
        $this->assertTrue(ByteRangeSpecification::create(0, 499)->equals($specification));
    }

    /** @dataProvider ignoredHeaderProvider */
    public function test_ignores_headers_it_cannot_read_as_a_single_specification(string $headerValue): void
    {
        $this->assertNull($this->parser->parse($headerValue));
    }
}
