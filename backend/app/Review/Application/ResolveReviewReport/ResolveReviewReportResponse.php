<?php

declare(strict_types=1);

namespace App\Review\Application\ResolveReviewReport;

use App\Review\Domain\Entity\ReviewReport;

final readonly class ResolveReviewReportResponse
{
    private function __construct(
        public int $reportId,
        public string $reviewId,
        public string $status,
        public string $updatedAt,
    ) {}

    public static function create(ReviewReport $report): self
    {
        $id = $report->id();

        if ($id === null) {
            throw new \LogicException('Cannot build ResolveReviewReportResponse from unpersisted ReviewReport.');
        }

        return new self(
            reportId: $id,
            reviewId: $report->reviewId()->value(),
            status: $report->status()->value(),
            updatedAt: $report->updatedAt()->value()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'report_id' => $this->reportId,
            'review_id' => $this->reviewId,
            'status' => $this->status,
            'updated_at' => $this->updatedAt,
        ];
    }
}
