<?php

declare(strict_types=1);

namespace App\Streaming\Application\GetStorageUsage;

use App\Streaming\Domain\ValueObject\DiskUsage;
use App\Streaming\Domain\ValueObject\StoragePrice;

final readonly class GetStorageUsageResponse
{
    /** @param list<array{disk: string, video_count: int, total_bytes: int, monthly_cost: float|null}> $disks */
    private function __construct(
        public array $disks,
        public int $totalBytes,
        public int $totalVideoCount,
    ) {}

    /** @param list<DiskUsage> $usages */
    public static function create(array $usages, ?StoragePrice $price): self
    {
        $disks = array_map(
            fn (DiskUsage $usage): array => [
                'disk' => $usage->disk()->value(),
                'video_count' => $usage->videoCount(),
                'total_bytes' => $usage->totalBytes(),
                'monthly_cost' => $price !== null && $usage->disk()->isR2()
                    ? $price->monthlyCostForAccount($usage->totalBytes())
                    : null,
            ],
            $usages,
        );

        return new self(
            disks: $disks,
            totalBytes: array_sum(array_column($disks, 'total_bytes')),
            totalVideoCount: array_sum(array_column($disks, 'video_count')),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'disks' => $this->disks,
            'total_bytes' => $this->totalBytes,
            'total_video_count' => $this->totalVideoCount,
        ];
    }
}
