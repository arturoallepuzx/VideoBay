<?php

declare(strict_types=1);

namespace App\Streaming\Domain\ValueObject;

final readonly class DiskUsage
{
    private function __construct(
        private StorageDisk $disk,
        private int $videoCount,
        private int $totalBytes,
    ) {}

    public static function create(StorageDisk $disk, int $videoCount, int $totalBytes): self
    {
        if ($videoCount < 0) {
            throw new \InvalidArgumentException(sprintf('Invalid video count %d.', $videoCount));
        }

        if ($totalBytes < 0) {
            throw new \InvalidArgumentException(sprintf('Invalid total size %d.', $totalBytes));
        }

        return new self($disk, $videoCount, $totalBytes);
    }

    public function disk(): StorageDisk
    {
        return $this->disk;
    }

    public function videoCount(): int
    {
        return $this->videoCount;
    }

    public function totalBytes(): int
    {
        return $this->totalBytes;
    }
}
