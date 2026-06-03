<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

class SubtitleFileNotFoundException extends NotFoundException
{
    public static function forPath(string $path): self
    {
        return new self(sprintf('Subtitle file "%s" not found.', $path));
    }
}
