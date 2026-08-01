<?php

declare(strict_types=1);

namespace App\Streaming\Domain\ValueObject;

final readonly class StoredObject
{
    private function __construct(
        private string $name,
        private int $sizeBytes,
        private \DateTimeImmutable $modifiedAt,
        private StorageDisk $disk,
    ) {}

    public static function create(string $name, int $sizeBytes, \DateTimeImmutable $modifiedAt, StorageDisk $disk): self
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('Stored object name cannot be empty.');
        }

        if ($sizeBytes < 0) {
            throw new \InvalidArgumentException(sprintf('Invalid stored object size %d.', $sizeBytes));
        }

        return new self($name, $sizeBytes, $modifiedAt, $disk);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function sizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function modifiedAt(): \DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function disk(): StorageDisk
    {
        return $this->disk;
    }
}
