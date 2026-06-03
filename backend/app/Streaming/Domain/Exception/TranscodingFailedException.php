<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Exception;

class TranscodingFailedException extends \RuntimeException
{
    public static function ffmpegError(string $inputPath, string $errorOutput): self
    {
        return new self(sprintf('FFmpeg failed for "%s": %s', $inputPath, $errorOutput));
    }
}
