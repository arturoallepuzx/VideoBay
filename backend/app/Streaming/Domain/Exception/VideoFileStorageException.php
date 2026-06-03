<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Exception;

use App\Shared\Domain\Exception\ServiceUnavailableException;

class VideoFileStorageException extends ServiceUnavailableException
{
    public static function renameFailed(string $from, string $to): self
    {
        return new self(sprintf('Failed to rename "%s" to "%s".', $from, $to));
    }

    public static function moveFailed(string $from, string $to): self
    {
        return new self(sprintf('Failed to move "%s" to "%s".', $from, $to));
    }

    public static function deleteFailed(string $path): self
    {
        return new self(sprintf('Failed to delete "%s".', $path));
    }

    public static function directoryCreationFailed(string $path): self
    {
        return new self(sprintf('Failed to create directory "%s".', $path));
    }
}
