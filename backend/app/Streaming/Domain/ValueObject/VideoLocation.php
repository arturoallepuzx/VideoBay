<?php

declare(strict_types=1);

namespace App\Streaming\Domain\ValueObject;

final readonly class VideoLocation
{
    private function __construct(
        private StorageDisk $disk,
        private string $key,
    ) {}

    public static function create(StorageDisk $disk, string $key): self
    {
        $normalizedKey = trim($key);

        if ($normalizedKey === '') {
            throw new \InvalidArgumentException('Video location key cannot be empty.');
        }

        return new self($disk, $normalizedKey);
    }

    public function disk(): StorageDisk
    {
        return $this->disk;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function equals(self $other): bool
    {
        return $this->disk->equals($other->disk) && $this->key === $other->key;
    }
}
