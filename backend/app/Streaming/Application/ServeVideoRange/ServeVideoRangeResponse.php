<?php

declare(strict_types=1);

namespace App\Streaming\Application\ServeVideoRange;

use App\Streaming\Domain\ValueObject\ByteRange;

final readonly class ServeVideoRangeResponse
{
    /** @param iterable<string> $chunks */
    private function __construct(
        public iterable $chunks,
        public ?ByteRange $byteRange,
        public int $totalSizeBytes,
        public string $mimeType,
    ) {}

    /** @param iterable<string> $chunks */
    public static function create(iterable $chunks, ?ByteRange $byteRange, int $totalSizeBytes): self
    {
        return new self($chunks, $byteRange, $totalSizeBytes, 'video/mp4');
    }
}
