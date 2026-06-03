<?php

declare(strict_types=1);

namespace App\Streaming\Application\CleanupFailedOriginals;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoFileStorageInterface;

class CleanupFailedOriginals
{
    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
        private VideoFileStorageInterface $storage,
        private int $cleanupAfterDays,
    ) {}

    public function __invoke(): int
    {
        $threshold = DomainDateTime::create(
            (new \DateTimeImmutable)->modify(sprintf('-%d days', $this->cleanupAfterDays))
        );

        $videoFiles = $this->videoFileRepository->findFailedOlderThan($threshold);

        $deletedCount = 0;
        foreach ($videoFiles as $videoFile) {
            if ($this->cleanupOriginalOf($videoFile)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    private function cleanupOriginalOf(VideoFile $videoFile): bool
    {
        $originalPath = $videoFile->originalPath();
        if ($originalPath === null) {
            return false;
        }

        $this->storage->delete($originalPath);
        $videoFile->clearOriginalReference();
        $this->videoFileRepository->update($videoFile);

        return true;
    }
}
