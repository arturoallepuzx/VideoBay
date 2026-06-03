<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Interfaces;

interface VideoFileStorageInterface
{
    public function existsInRoot(string $filename): bool;

    public function existsInOriginals(string $filename): bool;

    /** @return list<array{filename: string, size_bytes: int, modified_at: \DateTimeImmutable}> */
    public function listFilesInRoot(): array;

    /** @return list<array{filename: string, size_bytes: int, modified_at: \DateTimeImmutable}> */
    public function listFilesInOriginals(): array;

    public function rename(string $fromAbsolutePath, string $toAbsolutePath): void;

    public function moveUploadedTo(string $temporaryAbsolutePath, string $destinationAbsolutePath): void;

    public function delete(string $absolutePath): void;

    public function fileSize(string $absolutePath): int;

    public function absolutePathInRoot(string $filename): string;

    public function absolutePathInOriginals(string $filename): string;
}
