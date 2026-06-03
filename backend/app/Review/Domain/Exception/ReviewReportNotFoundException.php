<?php

declare(strict_types=1);

namespace App\Review\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

class ReviewReportNotFoundException extends NotFoundException
{
    public static function forId(int $id): self
    {
        return new self(sprintf('Review report with id %d not found.', $id));
    }
}
