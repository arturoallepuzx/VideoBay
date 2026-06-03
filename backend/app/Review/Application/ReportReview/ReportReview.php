<?php

declare(strict_types=1);

namespace App\Review\Application\ReportReview;

use App\Review\Domain\Entity\ReviewReport;
use App\Review\Domain\Exception\ReviewNotFoundException;
use App\Review\Domain\Interfaces\ReviewReportRepositoryInterface;
use App\Review\Domain\Interfaces\ReviewRepositoryInterface;
use App\Review\Domain\ValueObject\ReviewReportReason;
use App\Shared\Domain\ValueObject\Uuid;

class ReportReview
{
    public function __construct(
        private ReviewRepositoryInterface $reviewRepository,
        private ReviewReportRepositoryInterface $reportRepository,
    ) {}

    public function __invoke(string $reviewUuid, string $reporterUuid, string $reason): ReportReviewResponse
    {
        $reviewId = Uuid::create($reviewUuid);
        $reporterId = Uuid::create($reporterUuid);

        $review = $this->reviewRepository->findByUuid($reviewId);

        if ($review === null || $review->isDeleted()) {
            throw ReviewNotFoundException::forUuid($reviewId);
        }

        $existing = $this->reportRepository->findPendingByUserAndReview($reviewId, $reporterId);

        if ($existing !== null) {
            return ReportReviewResponse::create($existing);
        }

        $report = ReviewReport::dddCreate($reviewId, $reporterId, ReviewReportReason::create($reason));
        $persisted = $this->reportRepository->create($report);

        return ReportReviewResponse::create($persisted);
    }
}
