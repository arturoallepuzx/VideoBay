<?php

declare(strict_types=1);

namespace App\Streaming\Application\FailVideoFileRegistration;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;

class FailVideoFileRegistration
{
    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(string $videoFileUuid, string $reason): void
    {
        $uuid = Uuid::create($videoFileUuid);

        $this->transactionRunner->run(function () use ($uuid, $reason): void {
            $videoFile = $this->videoFileRepository->findByUuidForUpdate($uuid);

            if ($videoFile === null || ! $videoFile->processingStatus()->isProcessing()) {
                return;
            }

            $videoFile->markFailed($reason);
            $this->videoFileRepository->update($videoFile);
        });
    }
}
