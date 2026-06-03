<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Services;

use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Interfaces\TranscodingJobDispatcherInterface;
use App\Streaming\Infrastructure\Jobs\TranscodeVideoJob;
use Illuminate\Contracts\Bus\Dispatcher;

class LaravelTranscodingJobDispatcher implements TranscodingJobDispatcherInterface
{
    public function __construct(private Dispatcher $dispatcher) {}

    public function dispatch(Uuid $videoFileUuid): void
    {
        $this->dispatcher->dispatch(new TranscodeVideoJob($videoFileUuid->value()));
    }
}
