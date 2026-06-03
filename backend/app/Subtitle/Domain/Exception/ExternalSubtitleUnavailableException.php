<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Exception;

use App\Shared\Domain\Exception\ServiceUnavailableException;

class ExternalSubtitleUnavailableException extends ServiceUnavailableException
{
    public static function unavailable(?\Throwable $previous = null): self
    {
        return new self('External subtitle provider unavailable.', 0, $previous);
    }

    public static function unexpectedResponse(int $status, string $body): self
    {
        return new self(sprintf('External subtitle provider returned HTTP %d: %s', $status, mb_substr($body, 0, 200)));
    }
}
