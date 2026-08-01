<?php

declare(strict_types=1);

namespace App\Streaming\Application\FinishVideoFileRegistration;

use App\Shared\Domain\Interfaces\DomainEventDispatcherInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Event\VideoFileReady;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;
use App\Streaming\Domain\Interfaces\VideoMetadataProbeInterface;

class FinishVideoFileRegistration
{
    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
        private VideoFileStorageInterface $storage,
        private VideoMetadataProbeInterface $metadataProbe,
        private TransactionRunnerInterface $transactionRunner,
        private DomainEventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(string $videoFileUuid): void
    {
        $uuid = Uuid::create($videoFileUuid);
        $videoFile = $this->videoFileRepository->findByUuid($uuid);

        if ($videoFile === null || ! $videoFile->processingStatus()->isProcessing()) {
            return;
        }

        $sourceLocation = $videoFile->originalLocation();

        if ($sourceLocation === null) {
            return;
        }

        $targetLocation = $this->storage->locationForVideo($uuid->value().'.mp4');

        if ($this->storage->exists($sourceLocation)) {
            $this->storage->rename($sourceLocation, $targetLocation);
        }

        $metadata = $this->metadataProbe->probe($targetLocation);

        $becameReady = $this->transactionRunner->run(function () use ($uuid, $targetLocation, $metadata): bool {
            $videoFile = $this->videoFileRepository->findByUuidForUpdate($uuid);

            if ($videoFile === null || ! $videoFile->processingStatus()->isProcessing()) {
                return false;
            }

            $videoFile->markReady($targetLocation, $metadata);
            $videoFile->clearOriginalReference();
            $this->videoFileRepository->update($videoFile);

            return true;
        });

        if ($becameReady) {
            $this->eventDispatcher->dispatch(VideoFileReady::create($videoFile->id(), $videoFile->movieId()));
        }
    }
}
