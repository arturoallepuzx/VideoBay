<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

class SubtitleReportNotFoundException extends NotFoundException
{
    public static function forId(int $id): self
    {
        return new self(sprintf('Subtitle report "%d" not found.', $id));
    }
}
