<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Exception;

use App\Shared\Domain\Exception\ValidationException;

class InvalidSubtitleReportDecisionException extends ValidationException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid subtitle report decision "%s".', $value));
    }
}
