<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Exception;

use App\Shared\Domain\Exception\ServiceUnavailableException;

class TmdbServiceUnavailableException extends ServiceUnavailableException
{
    public static function unreachable(\Throwable $previous): self
    {
        return new self('TMDB service is unreachable.', 0, $previous);
    }

    public static function unexpectedResponse(int $statusCode, string $body): self
    {
        return new self(
            sprintf('TMDB returned unexpected status %d: %s', $statusCode, mb_substr($body, 0, 200)),
        );
    }
}
