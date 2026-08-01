<?php

declare(strict_types=1);

namespace App\Streaming\Domain\ValueObject;

use App\Streaming\Domain\Exception\InvalidVideoRangeException;

final readonly class ByteRangeSpecification
{
    private function __construct(
        private ?int $firstBytePos,
        private ?int $lastBytePos,
        private ?int $suffixLength,
    ) {}

    public static function create(int $firstBytePos, ?int $lastBytePos): self
    {
        if ($firstBytePos < 0) {
            throw new \InvalidArgumentException(sprintf('Invalid first byte position %d.', $firstBytePos));
        }

        if ($lastBytePos !== null && $lastBytePos < $firstBytePos) {
            throw new \InvalidArgumentException(sprintf('Invalid byte range specification %d-%d.', $firstBytePos, $lastBytePos));
        }

        return new self($firstBytePos, $lastBytePos, null);
    }

    public static function fromSuffixLength(int $suffixLength): self
    {
        if ($suffixLength < 0) {
            throw new \InvalidArgumentException(sprintf('Invalid suffix length %d.', $suffixLength));
        }

        return new self(null, null, $suffixLength);
    }

    public function resolveAgainst(int $totalSizeBytes): ByteRange
    {
        if ($totalSizeBytes <= 0) {
            throw InvalidVideoRangeException::forRequestedRange($totalSizeBytes);
        }

        if ($this->suffixLength !== null) {
            return $this->resolveTrailingBytes($this->suffixLength, $totalSizeBytes);
        }

        if ($this->firstBytePos !== null) {
            return $this->resolveFrom($this->firstBytePos, $this->lastBytePos, $totalSizeBytes);
        }

        throw new \LogicException('A byte range specification must have either a first byte position or a suffix length.');
    }

    public function equals(self $other): bool
    {
        return $this->firstBytePos === $other->firstBytePos
            && $this->lastBytePos === $other->lastBytePos
            && $this->suffixLength === $other->suffixLength;
    }

    private function resolveFrom(int $firstBytePos, ?int $lastBytePos, int $totalSizeBytes): ByteRange
    {
        if ($firstBytePos >= $totalSizeBytes) {
            throw InvalidVideoRangeException::forRequestedRange($totalSizeBytes);
        }

        $end = $lastBytePos === null ? $totalSizeBytes - 1 : min($lastBytePos, $totalSizeBytes - 1);

        return ByteRange::create($firstBytePos, $end);
    }

    private function resolveTrailingBytes(int $suffixLength, int $totalSizeBytes): ByteRange
    {
        if ($suffixLength === 0) {
            throw InvalidVideoRangeException::forRequestedRange($totalSizeBytes);
        }

        return ByteRange::create(max(0, $totalSizeBytes - $suffixLength), $totalSizeBytes - 1);
    }
}
