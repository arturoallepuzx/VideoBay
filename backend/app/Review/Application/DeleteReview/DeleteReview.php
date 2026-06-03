<?php

declare(strict_types=1);

namespace App\Review\Application\DeleteReview;

use App\Review\Domain\Exception\ReviewAuthorMismatchException;
use App\Review\Domain\Exception\ReviewNotFoundException;
use App\Review\Domain\Interfaces\ReviewRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class DeleteReview
{
    public function __construct(
        private ReviewRepositoryInterface $reviewRepository,
    ) {}

    public function __invoke(string $reviewUuid, string $requesterUuid, bool $requesterIsAdmin): void
    {
        $reviewId = Uuid::create($reviewUuid);
        $requesterId = Uuid::create($requesterUuid);

        $review = $this->reviewRepository->findByUuid($reviewId);

        if ($review === null || $review->isDeleted()) {
            throw ReviewNotFoundException::forUuid($reviewId);
        }

        if (! $requesterIsAdmin && ! $review->isOwnedBy($requesterId)) {
            throw ReviewAuthorMismatchException::cannotDelete();
        }

        $review->softDelete();

        if ($review->wasModified()) {
            $this->reviewRepository->update($review);
        }
    }
}
