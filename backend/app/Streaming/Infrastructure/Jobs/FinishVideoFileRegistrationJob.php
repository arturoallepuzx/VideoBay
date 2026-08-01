<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Jobs;

use App\Streaming\Application\FailVideoFileRegistration\FailVideoFileRegistration;
use App\Streaming\Application\FinishVideoFileRegistration\FinishVideoFileRegistration;
use App\Streaming\Domain\Exception\InvalidVideoFormatException;
use App\Streaming\Domain\Exception\VideoFileStorageException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FinishVideoFileRegistrationJob implements ShouldQueue
{
    use Queueable;

    private const CONNECTION = 'redis_storage';

    private const QUEUE = 'storage';

    private const UNEXPECTED_FAILURE_REASON = 'The video could not be moved to its final location.';

    public int $tries = 3;

    public int $timeout = 600;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public function __construct(
        private string $videoFileUuid,
    ) {
        $this->onConnection(self::CONNECTION);
        $this->onQueue(self::QUEUE);
    }

    public function handle(FinishVideoFileRegistration $finishVideoFileRegistration): void
    {
        ($finishVideoFileRegistration)($this->videoFileUuid);
    }

    public function failed(\Throwable $exception): void
    {
        $failVideoFileRegistration = app(FailVideoFileRegistration::class);

        ($failVideoFileRegistration)($this->videoFileUuid, $this->reasonFor($exception));
    }

    private function reasonFor(\Throwable $exception): string
    {
        return $exception instanceof VideoFileStorageException || $exception instanceof InvalidVideoFormatException
            ? $exception->getMessage()
            : self::UNEXPECTED_FAILURE_REASON;
    }
}
