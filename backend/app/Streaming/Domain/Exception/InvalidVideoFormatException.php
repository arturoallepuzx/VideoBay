<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Exception;

use App\Shared\Domain\Exception\ValidationException;
use App\Streaming\Domain\ValueObject\VideoLocation;

class InvalidVideoFormatException extends ValidationException
{
    public static function forLocation(VideoLocation $location): self
    {
        return new self(sprintf('Cannot read video metadata from "%s".', $location->key()));
    }
}
