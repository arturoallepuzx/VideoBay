<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Services;

use App\Shared\Domain\Interfaces\DomainEventDispatcherInterface;
use Illuminate\Contracts\Events\Dispatcher;

class LaravelDomainEventDispatcher implements DomainEventDispatcherInterface
{
    public function __construct(private Dispatcher $dispatcher) {}

    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
