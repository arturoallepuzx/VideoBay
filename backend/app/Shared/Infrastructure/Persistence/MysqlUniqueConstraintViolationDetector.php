<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use Illuminate\Database\QueryException;

class MysqlUniqueConstraintViolationDetector
{
    private const MYSQL_DUPLICATE_ENTRY = 1062;

    public function matches(QueryException $exception, string $constraintName): bool
    {
        $driverErrorCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = (string) ($exception->errorInfo[2] ?? $exception->getMessage());

        return $driverErrorCode === self::MYSQL_DUPLICATE_ENTRY
            && str_contains($message, $constraintName);
    }
}
