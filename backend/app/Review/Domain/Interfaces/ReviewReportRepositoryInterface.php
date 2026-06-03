<?php

declare(strict_types=1);

namespace App\Review\Domain\Interfaces;

use App\Review\Domain\Entity\ReviewReport;
use App\Shared\Domain\ValueObject\Uuid;

interface ReviewReportRepositoryInterface
{
    public function findById(int $id): ?ReviewReport;

    public function findByIdForUpdate(int $id): ?ReviewReport;

    public function findPendingByUserAndReview(Uuid $reviewId, Uuid $userId): ?ReviewReport;

    /**
     * @return list<ReviewReport>
     */
    public function findPendingByReviewForUpdate(Uuid $reviewId): array;

    public function create(ReviewReport $report): ReviewReport;

    public function update(ReviewReport $report): void;

    /**
     * @return array{items: list<ReviewReport>, total: int, page: int, totalPages: int}
     */
    public function listPending(int $page, int $perPage): array;
}
