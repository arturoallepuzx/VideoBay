<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\ValueObject\SubtitleReportReason;
use App\Subtitle\Domain\ValueObject\SubtitleReportStatus;

class SubtitleReport
{
    private bool $modified = false;

    private function __construct(
        private ?int $id,
        private Uuid $subtitleId,
        private Uuid $reportedByUserId,
        private SubtitleReportReason $reason,
        private SubtitleReportStatus $status,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(Uuid $subtitleId, Uuid $reportedByUserId, SubtitleReportReason $reason): self
    {
        $now = DomainDateTime::now();

        return new self(
            null,
            $subtitleId,
            $reportedByUserId,
            $reason,
            SubtitleReportStatus::pending(),
            $now,
            $now,
        );
    }

    public static function fromPersistence(
        int $id,
        string $subtitleId,
        string $reportedByUserId,
        string $reason,
        string $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            $id,
            Uuid::create($subtitleId),
            Uuid::create($reportedByUserId),
            SubtitleReportReason::create($reason),
            SubtitleReportStatus::create($status),
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function resolve(): void
    {
        if (! $this->status->isPending()) {
            return;
        }

        $this->status = SubtitleReportStatus::resolved();
        $this->touch();
    }

    public function dismiss(): void
    {
        if (! $this->status->isPending()) {
            return;
        }

        $this->status = SubtitleReportStatus::dismissed();
        $this->touch();
    }

    public function wasModified(): bool
    {
        return $this->modified;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function subtitleId(): Uuid
    {
        return $this->subtitleId;
    }

    public function reportedByUserId(): Uuid
    {
        return $this->reportedByUserId;
    }

    public function reason(): SubtitleReportReason
    {
        return $this->reason;
    }

    public function status(): SubtitleReportStatus
    {
        return $this->status;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->modified = true;
        $this->updatedAt = DomainDateTime::now();
    }
}
