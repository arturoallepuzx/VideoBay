<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Exception;

use App\Shared\Domain\Exception\ConflictException;
use App\Shared\Domain\ValueObject\Uuid;

class VideoFileNotReadyException extends ConflictException
{
    public static function forUuid(Uuid $uuid, string $currentStatus): self
    {
        return new self(sprintf(
            'Video file "%s" is not ready for streaming (current status: %s).',
            $uuid->value(),
            $currentStatus
        ));
    }
}
