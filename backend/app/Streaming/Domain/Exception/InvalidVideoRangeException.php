<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Exception;

use App\Shared\Domain\Exception\ValidationException;

class InvalidVideoRangeException extends ValidationException
{
    private function __construct(string $message, private int $totalSizeBytes)
    {
        parent::__construct($message);
    }

    public static function forRequestedRange(int $totalSizeBytes): self
    {
        return new self('The requested range cannot be satisfied.', $totalSizeBytes);
    }

    public function totalSizeBytes(): int
    {
        return $this->totalSizeBytes;
    }
}
