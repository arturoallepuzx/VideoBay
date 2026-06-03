<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Services;

use App\Streaming\Domain\Exception\VideoFileStorageException;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;

class LocalVideoFileStorage implements VideoFileStorageInterface
{
    private string $videosPath;

    private string $originalsPath;

    public function __construct(string $videosPath, string $originalsSubdir)
    {
        $this->videosPath = rtrim($videosPath, '/');
        $this->originalsPath = $this->videosPath.'/'.trim($originalsSubdir, '/');

        $this->ensureDirectoryExists($this->videosPath);
        $this->ensureDirectoryExists($this->originalsPath);
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (! is_dir($path) && ! @mkdir($path, 0775, true) && ! is_dir($path)) {
            throw VideoFileStorageException::directoryCreationFailed($path);
        }
    }

    public function existsInRoot(string $filename): bool
    {
        return file_exists($this->absolutePathInRoot($filename));
    }

    public function existsInOriginals(string $filename): bool
    {
        return file_exists($this->absolutePathInOriginals($filename));
    }

    public function listFilesInRoot(): array
    {
        return $this->listFilesIn($this->videosPath);
    }

    public function listFilesInOriginals(): array
    {
        return $this->listFilesIn($this->originalsPath);
    }

    public function rename(string $fromAbsolutePath, string $toAbsolutePath): void
    {
        if (! rename($fromAbsolutePath, $toAbsolutePath)) {
            throw VideoFileStorageException::renameFailed($fromAbsolutePath, $toAbsolutePath);
        }
    }

    public function moveUploadedTo(string $temporaryAbsolutePath, string $destinationAbsolutePath): void
    {
        if (@rename($temporaryAbsolutePath, $destinationAbsolutePath)) {
            return;
        }

        if (! @copy($temporaryAbsolutePath, $destinationAbsolutePath)) {
            throw VideoFileStorageException::moveFailed($temporaryAbsolutePath, $destinationAbsolutePath);
        }

        @unlink($temporaryAbsolutePath);
    }

    public function delete(string $absolutePath): void
    {
        if (file_exists($absolutePath) && ! unlink($absolutePath)) {
            throw VideoFileStorageException::deleteFailed($absolutePath);
        }
    }

    public function fileSize(string $absolutePath): int
    {
        $size = filesize($absolutePath);

        return $size !== false ? $size : 0;
    }

    public function absolutePathInRoot(string $filename): string
    {
        return $this->videosPath.'/'.$filename;
    }

    public function absolutePathInOriginals(string $filename): string
    {
        return $this->originalsPath.'/'.$filename;
    }

    /** @return list<array{filename: string, size_bytes: int, modified_at: \DateTimeImmutable}> */
    private function listFilesIn(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];
        foreach (new \DirectoryIterator($directory) as $file) {
            if ($file->isDot() || $file->isDir() || ! $file->isFile()) {
                continue;
            }

            $files[] = [
                'filename' => $file->getFilename(),
                'size_bytes' => (int) $file->getSize(),
                'modified_at' => new \DateTimeImmutable('@'.$file->getMTime()),
            ];
        }

        usort($files, fn (array $a, array $b): int => $b['modified_at'] <=> $a['modified_at']);

        return $files;
    }
}
