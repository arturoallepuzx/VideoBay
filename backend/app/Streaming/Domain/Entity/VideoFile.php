<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\ValueObject\StorageDisk;
use App\Streaming\Domain\ValueObject\VideoFileProcessingStatus;
use App\Streaming\Domain\ValueObject\VideoLocation;
use App\Streaming\Domain\ValueObject\VideoMetadata;

class VideoFile
{
    private bool $modified = false;

    private function __construct(
        private Uuid $id,
        private Uuid $movieId,
        private ?string $originalFilename,
        private ?string $originalFormat,
        private ?VideoLocation $originalLocation,
        private ?VideoLocation $processedLocation,
        private ?VideoMetadata $metadata,
        private VideoFileProcessingStatus $processingStatus,
        private ?string $processingError,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $deletedAt,
    ) {}

    public static function dddCreate(
        Uuid $id,
        Uuid $movieId,
        ?string $originalFilename,
        ?string $originalFormat,
        ?VideoLocation $originalLocation,
        ?VideoLocation $processedLocation,
        ?VideoMetadata $metadata,
        VideoFileProcessingStatus $status,
    ): self {
        if ($status->isReady() && ($processedLocation === null || $metadata === null)) {
            throw new \InvalidArgumentException('A ready video file must have processed_path and metadata.');
        }

        if (($status->isPending() || $status->isProcessing()) && $originalLocation === null) {
            throw new \InvalidArgumentException('A pending or processing video file must have original_path.');
        }

        $now = DomainDateTime::now();

        return new self(
            $id,
            $movieId,
            $originalFilename,
            $originalFormat,
            $originalLocation,
            $processedLocation,
            $metadata,
            $status,
            null,
            $now,
            $now,
            null,
        );
    }

    public static function fromPersistence(
        string $id,
        string $movieId,
        ?string $originalFilename,
        ?string $originalFormat,
        ?string $originalPath,
        ?string $processedPath,
        string $storageDisk,
        ?int $durationSeconds,
        ?int $fileSizeBytes,
        ?string $audioLanguage,
        string $processingStatus,
        ?string $processingError,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $deletedAt,
    ): self {
        return new self(
            Uuid::create($id),
            Uuid::create($movieId),
            $originalFilename,
            $originalFormat,
            $originalPath !== null ? VideoLocation::create(StorageDisk::create($storageDisk), $originalPath) : null,
            $processedPath !== null ? VideoLocation::create(StorageDisk::create($storageDisk), $processedPath) : null,
            $durationSeconds !== null && $fileSizeBytes !== null
                ? VideoMetadata::create($durationSeconds, $fileSizeBytes, $audioLanguage)
                : null,
            VideoFileProcessingStatus::create($processingStatus),
            $processingError,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
            $deletedAt !== null ? DomainDateTime::create($deletedAt) : null,
        );
    }

    public function markProcessing(): void
    {
        $this->processingStatus = VideoFileProcessingStatus::processing();
        $this->touch();
    }

    public function markReady(VideoLocation $processedLocation, VideoMetadata $metadata): void
    {
        $this->processedLocation = $processedLocation;
        $this->metadata = $metadata;
        $this->processingStatus = VideoFileProcessingStatus::ready();
        $this->touch();
    }

    public function clearOriginalReference(): void
    {
        $this->originalLocation = null;
        $this->touch();
    }

    public function markFailed(string $errorMessage): void
    {
        $this->processingStatus = VideoFileProcessingStatus::failed();
        $this->processingError = $errorMessage;
        $this->touch();
    }

    public function reassignToMovie(Uuid $newMovieId): void
    {
        $this->movieId = $newMovieId;
        $this->touch();
    }

    public function softDelete(): void
    {
        if ($this->deletedAt !== null) {
            return;
        }

        $this->deletedAt = DomainDateTime::now();
        $this->touch();
    }

    public function isReady(): bool
    {
        return $this->processingStatus->isReady() && $this->deletedAt === null;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function wasModified(): bool
    {
        return $this->modified;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function movieId(): Uuid
    {
        return $this->movieId;
    }

    public function originalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function originalFormat(): ?string
    {
        return $this->originalFormat;
    }

    public function originalLocation(): ?VideoLocation
    {
        return $this->originalLocation;
    }

    public function processedLocation(): ?VideoLocation
    {
        return $this->processedLocation;
    }

    public function metadata(): ?VideoMetadata
    {
        return $this->metadata;
    }

    public function processingStatus(): VideoFileProcessingStatus
    {
        return $this->processingStatus;
    }

    public function processingError(): ?string
    {
        return $this->processingError;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DomainDateTime
    {
        return $this->deletedAt;
    }

    private function touch(): void
    {
        $this->modified = true;
        $this->updatedAt = DomainDateTime::now();
    }
}
