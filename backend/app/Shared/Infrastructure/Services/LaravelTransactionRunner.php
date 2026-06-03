<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Services;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use Illuminate\Support\Facades\DB;

class LaravelTransactionRunner implements TransactionRunnerInterface
{
    public function run(callable $callback, int $attempts = 3): mixed
    {
        return DB::transaction($callback, $attempts);
    }
}
