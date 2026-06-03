<?php

declare(strict_types=1);

namespace App\Streaming\Application\ReassignVideoFile;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\Entity\VideoFile;
use App\Streaming\Domain\Exception\VideoFileNotFoundException;
use App\Streaming\Domain\Interfaces\MovieResolverForStreamingInterface;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;

class ReassignVideoFile
{
    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
        private MovieResolverForStreamingInterface $movieResolver,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(
        string $videoFileUuid,
        ?string $movieUuid,
        ?int $tmdbId,
    ): ReassignVideoFileResponse {
        $uuid = Uuid::create($videoFileUuid);

        $newMovieId = $movieUuid !== null
            ? $this->movieResolver->resolveByUuid(Uuid::create($movieUuid))
            : $this->movieResolver->resolveByTmdbId((int) $tmdbId);

        $videoFile = $this->transactionRunner->run(function () use ($uuid, $newMovieId): VideoFile {
            $videoFile = $this->videoFileRepository->findByUuidForUpdate($uuid);

            if ($videoFile === null || $videoFile->isDeleted()) {
                throw VideoFileNotFoundException::forUuid($uuid);
            }

            $videoFile->reassignToMovie($newMovieId);

            if ($videoFile->wasModified()) {
                $this->videoFileRepository->update($videoFile);
            }

            return $videoFile;
        });

        return ReassignVideoFileResponse::create($videoFile);
    }
}
