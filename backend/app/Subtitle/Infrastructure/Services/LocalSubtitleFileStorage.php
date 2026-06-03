<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Services;

use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\Interfaces\SubtitleFileStorageInterface;

class LocalSubtitleFileStorage implements SubtitleFileStorageInterface
{
    public function __construct(private string $storagePath)
    {
        $this->storagePath = rtrim($storagePath, '/');

        if (! is_dir($this->storagePath) && ! @mkdir($this->storagePath, 0775, true) && ! is_dir($this->storagePath)) {
            throw new \RuntimeException(sprintf('Could not create subtitle storage directory "%s".', $this->storagePath));
        }
    }

    public function store(Uuid $subtitleId, string $contents): string
    {
        $filename = $subtitleId->value().'.vtt';
        $absolutePath = $this->absolutePath($filename);

        if (file_put_contents($absolutePath, $contents, LOCK_EX) === false) {
            throw new \RuntimeException(sprintf('Could not write subtitle file "%s".', $absolutePath));
        }

        return $filename;
    }

    public function delete(string $filePath): void
    {
        $absolutePath = $this->absolutePath($filePath);

        if (is_file($absolutePath) && ! unlink($absolutePath)) {
            throw new \RuntimeException(sprintf('Could not delete subtitle file "%s".', $absolutePath));
        }
    }

    public function absolutePath(string $filePath): string
    {
        return $this->storagePath.'/'.basename($filePath);
    }

    public function exists(string $filePath): bool
    {
        return is_file($this->absolutePath($filePath));
    }
}
