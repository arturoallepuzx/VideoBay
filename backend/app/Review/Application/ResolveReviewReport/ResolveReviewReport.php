<?php

declare(strict_types=1);

namespace App\Review\Application\ResolveReviewReport;

use App\Review\Domain\Entity\ReviewReport;
use App\Review\Domain\Event\ReviewRemovedByModeration;
use App\Review\Domain\Exception\InvalidReviewReportDecisionException;
use App\Review\Domain\Exception\ReviewReportNotFoundException;
use App\Review\Domain\Interfaces\ReviewReportRepositoryInterface;
use App\Review\Domain\Interfaces\ReviewRepositoryInterface;
use App\Shared\Domain\Interfaces\DomainEventDispatcherInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;

class ResolveReviewReport
{
    private const DECISION_RESOLVED = 'resolved';

    private const DECISION_DISMISSED = 'dismissed';

    public function __construct(
        private ReviewReportRepositoryInterface $reportRepository,
        private ReviewRepositoryInterface $reviewRepository,
        private TransactionRunnerInterface $transactionRunner,
        private DomainEventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(int $reportId, string $decision): ResolveReviewReportResponse
    {
        if ($decision !== self::DECISION_RESOLVED && $decision !== self::DECISION_DISMISSED) {
            throw InvalidReviewReportDecisionException::forValue($decision);
        }

        $report = $this->transactionRunner->run(function () use ($reportId, $decision) {
            $report = $this->reportRepository->findByIdForUpdate($reportId);

            if ($report === null) {
                throw ReviewReportNotFoundException::forId($reportId);
            }

            if ($decision === self::DECISION_RESOLVED) {
                $report = $this->resolvePendingReportsForReview($report);

                $review = $this->reviewRepository->findByUuidForUpdate($report->reviewId());
                if ($review !== null && ! $review->isDeleted()) {
                    $this->eventDispatcher->dispatch(ReviewRemovedByModeration::create(
                        $review->id(),
                        $review->userId(),
                        $review->movieId(),
                        $review->body()?->value(),
                    ));

                    $review->softDelete();
                    if ($review->wasModified()) {
                        $this->reviewRepository->update($review);
                    }
                }
            } else {
                $report->dismiss();
                if ($report->wasModified()) {
                    $this->reportRepository->update($report);
                }
            }

            return $report;
        });

        return ResolveReviewReportResponse::create($report);
    }

    private function resolvePendingReportsForReview(ReviewReport $selectedReport): ReviewReport
    {
        $resolvedSelectedReport = $selectedReport;

        foreach ($this->reportRepository->findPendingByReviewForUpdate($selectedReport->reviewId()) as $pendingReport) {
            $pendingReport->resolve();

            if ($pendingReport->wasModified()) {
                $this->reportRepository->update($pendingReport);
            }

            if ($pendingReport->id() === $selectedReport->id()) {
                $resolvedSelectedReport = $pendingReport;
            }
        }

        return $resolvedSelectedReport;
    }
}
