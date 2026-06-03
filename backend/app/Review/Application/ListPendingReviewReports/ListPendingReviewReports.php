<?php

declare(strict_types=1);

namespace App\Review\Application\ListPendingReviewReports;

use App\Review\Domain\Entity\Review;
use App\Review\Domain\Entity\ReviewReport;
use App\Review\Domain\Interfaces\ReviewReportRepositoryInterface;
use App\Review\Domain\Interfaces\ReviewRepositoryInterface;
use App\Review\Domain\Interfaces\UserDisplayResolverInterface;
use App\Shared\Domain\ValueObject\Uuid;

class ListPendingReviewReports
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private ReviewReportRepositoryInterface $reportRepository,
        private ReviewRepositoryInterface $reviewRepository,
        private UserDisplayResolverInterface $userDisplayResolver,
    ) {}

    public function __invoke(int $page, int $perPage): ListPendingReviewReportsResponse
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE));

        $result = $this->reportRepository->listPending($page, $perPage);

        /** @var list<ReviewReport> $reports */
        $reports = $result['items'];

        $reviews = $this->reviewRepository->findManyByUuids($this->reviewIds($reports));

        $reviewsByUuid = [];
        foreach ($reviews as $review) {
            $reviewsByUuid[$review->id()->value()] = $review;
        }

        $displays = $this->userDisplayResolver->resolveMany($this->userIds($reports, $reviews));

        return ListPendingReviewReportsResponse::create($result, $reviewsByUuid, $displays);
    }

    /**
     * @param  list<ReviewReport>  $reports
     * @return list<Uuid>
     */
    private function reviewIds(array $reports): array
    {
        $seen = [];
        $ids = [];

        foreach ($reports as $report) {
            $value = $report->reviewId()->value();
            if (! isset($seen[$value])) {
                $seen[$value] = true;
                $ids[] = $report->reviewId();
            }
        }

        return $ids;
    }

    /**
     * @param  list<ReviewReport>  $reports
     * @param  list<Review>  $reviews
     * @return list<Uuid>
     */
    private function userIds(array $reports, array $reviews): array
    {
        $seen = [];
        $ids = [];

        foreach ($reports as $report) {
            $value = $report->reportedByUserId()->value();
            if (! isset($seen[$value])) {
                $seen[$value] = true;
                $ids[] = $report->reportedByUserId();
            }
        }

        foreach ($reviews as $review) {
            $value = $review->userId()->value();
            if (! isset($seen[$value])) {
                $seen[$value] = true;
                $ids[] = $review->userId();
            }
        }

        return $ids;
    }
}
