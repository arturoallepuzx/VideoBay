<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Services;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Interfaces\VideoFileRegistrationJobDispatcherInterface;
use App\Streaming\Infrastructure\Jobs\FinishVideoFileRegistrationJob;
use Illuminate\Contracts\Bus\Dispatcher;

class LaravelVideoFileRegistrationJobDispatcher implements VideoFileRegistrationJobDispatcherInterface
{
    public function __construct(private Dispatcher $dispatcher) {}

    public function dispatch(Uuid $videoFileUuid): void
    {
        $this->dispatcher->dispatch(new FinishVideoFileRegistrationJob($videoFileUuid->value()));
    }
}
