<?php

declare(strict_types=1);

namespace App\Streaming\Application\EnqueueTranscodingFromExisting;

use App\Streaming\Domain\Entity\VideoFile;

final readonly class EnqueueTranscodingFromExistingResponse
{
    private function __construct(
        public string $id,
        public string $movieId,
        public string $processingStatus,
        public string $createdAt,
    ) {}

    public static function create(VideoFile $videoFile): self
    {
        return new self(
            id: $videoFile->id()->value(),
            movieId: $videoFile->movieId()->value(),
            processingStatus: $videoFile->processingStatus()->value(),
            createdAt: $videoFile->createdAt()->value()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'movie_id' => $this->movieId,
            'processing_status' => $this->processingStatus,
            'created_at' => $this->createdAt,
        ];
    }
}
