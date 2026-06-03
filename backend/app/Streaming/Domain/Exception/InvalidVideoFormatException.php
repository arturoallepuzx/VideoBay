<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Exception;

use App\Shared\Domain\Exception\ValidationException;

class InvalidVideoFormatException extends ValidationException
{
    public static function forFile(string $path, string $reason): self
    {
        return new self(sprintf('Invalid video file "%s": %s', $path, $reason));
    }
}
