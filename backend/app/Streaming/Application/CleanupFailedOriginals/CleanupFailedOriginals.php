<?php

declare(strict_types=1);

namespace App\Streaming\Application\CleanupFailedOriginals;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\Interfaces\VideoTranscodeStorageInterface;

class CleanupFailedOriginals
{
    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
        private VideoTranscodeStorageInterface $storage,
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
        $originalLocation = $videoFile->originalLocation();
        if ($originalLocation === null) {
            return false;
        }

        $this->storage->delete($originalLocation);
        $videoFile->clearOriginalReference();
        $this->videoFileRepository->update($videoFile);

        return true;
    }
}
