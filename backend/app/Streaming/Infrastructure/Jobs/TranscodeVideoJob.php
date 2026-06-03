<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Jobs;

use App\Streaming\Application\TranscodeVideo\TranscodeVideo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TranscodeVideoJob implements ShouldQueue
{
    use Queueable;

    private const CONNECTION = 'redis_transcoding';

    private const QUEUE = 'transcoding';

    public int $tries = 1;

    public int $timeout = 86400;

    public function __construct(
        private string $videoFileUuid,
    ) {
        $this->onConnection(self::CONNECTION);
        $this->onQueue(self::QUEUE);
    }

    public function handle(TranscodeVideo $transcodeVideo): void
    {
        ($transcodeVideo)($this->videoFileUuid);
    }
}
