<?php

declare(strict_types=1);

namespace App\Shared\Domain\Interfaces;

interface DomainEventDispatcherInterface
{
    public function dispatch(object $event): void;
}
