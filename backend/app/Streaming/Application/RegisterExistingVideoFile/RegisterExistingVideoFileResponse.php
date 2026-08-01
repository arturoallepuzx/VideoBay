<?php

declare(strict_types=1);

namespace App\Streaming\Application\RegisterExistingVideoFile;

use App\Streaming\Domain\Entity\VideoFile;

final readonly class RegisterExistingVideoFileResponse
{
    private function __construct(
        public string $id,
        public string $movieId,
        public string $storageDisk,
        public ?int $durationSeconds,
        public ?int $fileSizeBytes,
        public ?string $audioLanguage,
        public string $processingStatus,
        public string $createdAt,
    ) {}

    public static function create(VideoFile $videoFile): self
    {
        return new self(
            id: $videoFile->id()->value(),
            movieId: $videoFile->movieId()->value(),
            storageDisk: (string) $videoFile->processedLocation()?->disk()->value(),
            durationSeconds: $videoFile->metadata()?->durationSeconds(),
            fileSizeBytes: $videoFile->metadata()?->fileSizeBytes(),
            audioLanguage: $videoFile->metadata()?->audioLanguage(),
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
            'storage_disk' => $this->storageDisk,
            'duration_seconds' => $this->durationSeconds,
            'file_size_bytes' => $this->fileSizeBytes,
            'audio_language' => $this->audioLanguage,
            'processing_status' => $this->processingStatus,
            'created_at' => $this->createdAt,
        ];
    }
}
