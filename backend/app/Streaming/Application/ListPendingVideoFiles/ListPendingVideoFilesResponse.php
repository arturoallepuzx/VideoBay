<?php

declare(strict_types=1);

namespace App\Streaming\Application\ListPendingVideoFiles;

final readonly class ListPendingVideoFilesResponse
{
    /**
     * @param  list<array{filename: string, size_bytes: int, modified_at: string}>  $rootFiles
     * @param  list<array{filename: string, size_bytes: int, modified_at: string}>  $originalsFiles
     */
    private function __construct(
        public array $rootFiles,
        public array $originalsFiles,
    ) {}

    /**
     * @param  list<array{filename: string, size_bytes: int, modified_at: \DateTimeImmutable}>  $root
     * @param  list<array{filename: string, size_bytes: int, modified_at: \DateTimeImmutable}>  $originals
     */
    public static function create(array $root, array $originals): self
    {
        $format = fn (array $f): array => [
            'filename' => $f['filename'],
            'size_bytes' => $f['size_bytes'],
            'modified_at' => $f['modified_at']->format(\DateTimeInterface::ATOM),
        ];

        return new self(
            rootFiles: array_map($format, $root),
            originalsFiles: array_map($format, $originals),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'root' => $this->rootFiles,
            'originals' => $this->originalsFiles,
        ];
    }
}
