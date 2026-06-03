<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\Uuid;

class VideoFileNotFoundException extends NotFoundException
{
    public static function forUuid(Uuid $uuid): self
    {
        return new self(sprintf('Video file with uuid "%s" not found.', $uuid->value()));
    }

    public static function forSourcePath(string $path): self
    {
        return new self(sprintf('Source video file "%s" not found in pending directory.', $path));
    }
}
