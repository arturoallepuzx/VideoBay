<?php

declare(strict_types=1);

namespace App\Streaming\Application\DeleteVideoFile;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Exception\VideoFileNotFoundException;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;

class DeleteVideoFile
{
    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
        private VideoFileStorageInterface $storage,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(string $videoFileUuid): void
    {
        $uuid = Uuid::create($videoFileUuid);
        $videoFile = $this->videoFileRepository->findByUuid($uuid);

        if ($videoFile === null || $videoFile->isDeleted()) {
            throw VideoFileNotFoundException::forUuid($uuid);
        }

        $this->deleteStoredFiles($videoFile);

        $this->transactionRunner->run(function () use ($uuid): void {
            $videoFile = $this->videoFileRepository->findByUuidForUpdate($uuid);

            if ($videoFile === null || $videoFile->isDeleted()) {
                throw VideoFileNotFoundException::forUuid($uuid);
            }

            $videoFile->softDelete();
            $this->videoFileRepository->update($videoFile);
        });
    }

    private function deleteStoredFiles(VideoFile $videoFile): void
    {
        $processedLocation = $videoFile->processedLocation();
        if ($processedLocation !== null) {
            $this->storage->delete($processedLocation);
        }

        $originalLocation = $videoFile->originalLocation();
        if ($originalLocation !== null) {
            $this->storage->delete($originalLocation);
        }
    }
}
