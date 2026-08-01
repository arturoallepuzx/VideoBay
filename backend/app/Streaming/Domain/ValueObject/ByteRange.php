<?php

declare(strict_types=1);

namespace App\Streaming\Domain\ValueObject;

final readonly class ByteRange
{
    private function __construct(
        private int $start,
        private int $end,
    ) {}

    public static function create(int $start, int $end): self
    {
        if ($start < 0 || $end < $start) {
            throw new \InvalidArgumentException(sprintf('Invalid byte range %d-%d.', $start, $end));
        }

        return new self($start, $end);
    }

    public function start(): int
    {
        return $this->start;
    }

    public function end(): int
    {
        return $this->end;
    }

    public function length(): int
    {
        return $this->end - $this->start + 1;
    }

    public function equals(self $other): bool
    {
        return $this->start === $other->start && $this->end === $other->end;
    }
}
