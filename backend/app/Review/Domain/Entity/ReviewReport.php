<?php

declare(strict_types=1);

namespace App\Review\Domain\Entity;

use App\Review\Domain\ValueObject\ReviewReportReason;
use App\Review\Domain\ValueObject\ReviewReportStatus;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class ReviewReport
{
    private bool $modified = false;

    private function __construct(
        private ?int $id,
        private Uuid $reviewId,
        private Uuid $reportedByUserId,
        private ReviewReportReason $reason,
        private ReviewReportStatus $status,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $reviewId,
        Uuid $reportedByUserId,
        ReviewReportReason $reason,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            null,
            $reviewId,
            $reportedByUserId,
            $reason,
            ReviewReportStatus::pending(),
            $now,
            $now,
        );
    }

    public static function fromPersistence(
        int $id,
        string $reviewId,
        string $reportedByUserId,
        string $reason,
        string $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            $id,
            Uuid::create($reviewId),
            Uuid::create($reportedByUserId),
            ReviewReportReason::create($reason),
            ReviewReportStatus::create($status),
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function resolve(): void
    {
        if (! $this->status->isPending()) {
            return;
        }

        $this->status = ReviewReportStatus::resolved();
        $this->touch();
    }

    public function dismiss(): void
    {
        if (! $this->status->isPending()) {
            return;
        }

        $this->status = ReviewReportStatus::dismissed();
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

    public function reviewId(): Uuid
    {
        return $this->reviewId;
    }

    public function reportedByUserId(): Uuid
    {
        return $this->reportedByUserId;
    }

    public function reason(): ReviewReportReason
    {
        return $this->reason;
    }

    public function status(): ReviewReportStatus
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
