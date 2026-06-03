<?php

declare(strict_types=1);

namespace App\Review\Application\ReportReview;

use App\Review\Domain\Entity\ReviewReport;

final readonly class ReportReviewResponse
{
    private function __construct(
        public int $reportId,
        public string $reviewId,
        public string $reason,
        public string $status,
    ) {}

    public static function create(ReviewReport $report): self
    {
        $id = $report->id();

        if ($id === null) {
            throw new \LogicException('Cannot build ReportReviewResponse from unpersisted ReviewReport.');
        }

        return new self(
            reportId: $id,
            reviewId: $report->reviewId()->value(),
            reason: $report->reason()->value(),
            status: $report->status()->value(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'report_id' => $this->reportId,
            'review_id' => $this->reviewId,
            'reason' => $this->reason,
            'status' => $this->status,
        ];
    }
}
