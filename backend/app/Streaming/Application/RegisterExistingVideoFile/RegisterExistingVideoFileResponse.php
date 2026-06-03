<?php

declare(strict_types=1);

namespace App\Streaming\Application\RegisterExistingVideoFile;

use App\Streaming\Domain\Entity\VideoFile;

final readonly class RegisterExistingVideoFileResponse
{
    private function __construct(
        public string $id,
        public string $movieId,
        public string $processedPath,
        public int $durationSeconds,
        public int $fileSizeBytes,
        public ?string $audioLanguage,
        public string $processingStatus,
        public string $createdAt,
    ) {}

    public static function create(VideoFile $videoFile): self
    {
        return new self(
            id: $videoFile->id()->value(),
            movieId: $videoFile->movieId()->value(),
            processedPath: (string) $videoFile->processedPath(),
            durationSeconds: (int) $videoFile->durationSeconds(),
            fileSizeBytes: (int) $videoFile->fileSizeBytes(),
            audioLanguage: $videoFile->audioLanguage(),
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
            'processed_path' => $this->processedPath,
            'duration_seconds' => $this->durationSeconds,
            'file_size_bytes' => $this->fileSizeBytes,
            'audio_language' => $this->audioLanguage,
            'processing_status' => $this->processingStatus,
            'created_at' => $this->createdAt,
        ];
    }
}
