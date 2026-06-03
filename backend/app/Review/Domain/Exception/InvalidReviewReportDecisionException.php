<?php

declare(strict_types=1);

namespace App\Review\Domain\Exception;

use App\Shared\Domain\Exception\ValidationException;

class InvalidReviewReportDecisionException extends ValidationException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf(
            'Invalid review report decision "%s". Allowed: resolved, dismissed.',
            $value,
        ));
    }
}
