<?php

declare(strict_types=1);

namespace App\Shared\Domain\Interfaces;

interface TransactionRunnerInterface
{
    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function run(callable $callback, int $attempts = 3): mixed;
}
