<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Streaming\Domain\ValueObject\VideoFileProcessingStatus;

class VideoFile
{
    private bool $modified = false;

    private function __construct(
        private Uuid $id,
        private Uuid $movieId,
        private ?string $originalFilename,
        private ?string $originalFormat,
        private ?string $originalPath,
        private ?string $processedPath,
        private ?int $durationSeconds,
        private ?int $fileSizeBytes,
        private ?string $audioLanguage,
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
        ?string $originalPath,
        ?string $processedPath,
        ?int $durationSeconds,
        ?int $fileSizeBytes,
        ?string $audioLanguage,
        VideoFileProcessingStatus $status,
    ): self {
        if ($status->isReady() && ($processedPath === null || $durationSeconds === null || $fileSizeBytes === null)) {
            throw new \InvalidArgumentException('A ready video file must have processed_path, duration_seconds and file_size_bytes.');
        }

        if ($status->isPending() && $originalPath === null) {
            throw new \InvalidArgumentException('A pending video file must have original_path.');
        }

        $now = DomainDateTime::now();

        return new self(
            $id,
            $movieId,
            $originalFilename,
            $originalFormat,
            $originalPath,
            $processedPath,
            $durationSeconds,
            $fileSizeBytes,
            $audioLanguage,
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
            $originalPath,
            $processedPath,
            $durationSeconds,
            $fileSizeBytes,
            $audioLanguage,
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

    public function markReady(string $processedPath, int $durationSeconds, int $fileSizeBytes, ?string $audioLanguage): void
    {
        $this->processedPath = $processedPath;
        $this->durationSeconds = $durationSeconds;
        $this->fileSizeBytes = $fileSizeBytes;
        $this->audioLanguage = $audioLanguage;
        $this->processingStatus = VideoFileProcessingStatus::ready();
        $this->touch();
    }

    public function clearOriginalReference(): void
    {
        $this->originalPath = null;
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

    public function originalPath(): ?string
    {
        return $this->originalPath;
    }

    public function processedPath(): ?string
    {
        return $this->processedPath;
    }

    public function durationSeconds(): ?int
    {
        return $this->durationSeconds;
    }

    public function fileSizeBytes(): ?int
    {
        return $this->fileSizeBytes;
    }

    public function audioLanguage(): ?string
    {
        return $this->audioLanguage;
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
