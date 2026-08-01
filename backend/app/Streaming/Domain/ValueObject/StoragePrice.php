<?php

declare(strict_types=1);

namespace App\Streaming\Domain\ValueObject;

final readonly class StoragePrice
{
    private const BYTES_PER_GIGABYTE = 1000000000;

    private function __construct(
        private float $perGigabyteMonth,
        private float $freeGigabytes,
    ) {}

    public static function create(float $perGigabyteMonth, float $freeGigabytes): self
    {
        if ($perGigabyteMonth < 0) {
            throw new \InvalidArgumentException(sprintf('Invalid storage price %f.', $perGigabyteMonth));
        }

        if ($freeGigabytes < 0) {
            throw new \InvalidArgumentException(sprintf('Invalid free storage allowance %f.', $freeGigabytes));
        }

        return new self($perGigabyteMonth, $freeGigabytes);
    }

    public function monthlyCostFor(int $bytes): float
    {
        return $this->gigabytesOf($bytes) * $this->perGigabyteMonth;
    }

    public function monthlyCostForAccount(int $bytes): float
    {
        return max(0.0, $this->gigabytesOf($bytes) - $this->freeGigabytes) * $this->perGigabyteMonth;
    }

    private function gigabytesOf(int $bytes): float
    {
        return $bytes / self::BYTES_PER_GIGABYTE;
    }
}
