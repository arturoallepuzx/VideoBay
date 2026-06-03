<?php

declare(strict_types=1);

namespace App\Review\Application\UpdateReview;

use App\Review\Domain\Exception\ReviewAuthorMismatchException;
use App\Review\Domain\Exception\ReviewNotFoundException;
use App\Review\Domain\Interfaces\ReviewRepositoryInterface;
use App\Review\Domain\ValueObject\ReviewBody;
use App\Review\Domain\ValueObject\ReviewRating;
use App\Shared\Domain\ValueObject\Uuid;

class UpdateReview
{
    public function __construct(
        private ReviewRepositoryInterface $reviewRepository,
    ) {}

    public function __invoke(
        string $reviewUuid,
        string $requesterUuid,
        ?int $rating,
        ?string $body,
        bool $bodyProvided,
        ?bool $containsSpoilers,
    ): UpdateReviewResponse {
        $reviewId = Uuid::create($reviewUuid);
        $requesterId = Uuid::create($requesterUuid);

        $review = $this->reviewRepository->findByUuid($reviewId);

        if ($review === null || $review->isDeleted()) {
            throw ReviewNotFoundException::forUuid($reviewId);
        }

        if (! $review->isOwnedBy($requesterId)) {
            throw ReviewAuthorMismatchException::cannotEdit();
        }

        if ($rating !== null) {
            $review->updateRating(ReviewRating::create($rating));
        }

        if ($bodyProvided) {
            $review->updateBody($body !== null ? ReviewBody::create($body) : null);
        }

        if ($containsSpoilers !== null) {
            $review->updateContainsSpoilers($containsSpoilers);
        }

        if ($review->wasModified()) {
            $this->reviewRepository->update($review);
        }

        return UpdateReviewResponse::create($review);
    }
}
