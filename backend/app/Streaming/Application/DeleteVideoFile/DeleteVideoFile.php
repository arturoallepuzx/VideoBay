<?php

declare(strict_types=1);

namespace App\Streaming\Application\DeleteVideoFile;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
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

        $videoFile = $this->transactionRunner->run(function () use ($uuid) {
            $videoFile = $this->videoFileRepository->findByUuidForUpdate($uuid);

            if ($videoFile === null || $videoFile->isDeleted()) {
                throw VideoFileNotFoundException::forUuid($uuid);
            }

            $videoFile->softDelete();
            $this->videoFileRepository->update($videoFile);

            return $videoFile;
        });

        if ($videoFile->processedPath() !== null) {
            $this->storage->delete($videoFile->processedPath());
        }

        if ($videoFile->originalPath() !== null) {
            $this->storage->delete($videoFile->originalPath());
        }
    }
}
