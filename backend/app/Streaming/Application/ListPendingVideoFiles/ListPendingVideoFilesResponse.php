<?php

declare(strict_types=1);

namespace App\Streaming\Application\ListPendingVideoFiles;

use App\Streaming\Domain\ValueObject\StorageDisk;
use App\Streaming\Domain\ValueObject\StoredObject;

final readonly class ListPendingVideoFilesResponse
{
    /**
     * @param  list<array{filename: string, size_bytes: int, modified_at: string}>  $rootFiles
     * @param  list<array{filename: string, size_bytes: int, modified_at: string}>  $originalsFiles
     */
    private function __construct(
        public array $rootFiles,
        public array $originalsFiles,
        public string $ingestionDisk,
    ) {}

    /**
     * @param  list<StoredObject>  $root
     * @param  list<StoredObject>  $originals
     */
    public static function create(array $root, array $originals, StorageDisk $ingestionDisk): self
    {
        $format = fn (StoredObject $object): array => [
            'filename' => $object->name(),
            'size_bytes' => $object->sizeBytes(),
            'modified_at' => $object->modifiedAt()->format(\DateTimeInterface::ATOM),
        ];

        return new self(
            rootFiles: array_map($format, $root),
            originalsFiles: array_map($format, $originals),
            ingestionDisk: $ingestionDisk->value(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'root' => $this->rootFiles,
            'originals' => $this->originalsFiles,
            'ingestion_disk' => $this->ingestionDisk,
        ];
    }
}
