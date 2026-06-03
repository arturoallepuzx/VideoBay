<?php

declare(strict_types=1);

namespace App\Review\Application\CreateReview;

use App\Review\Domain\Entity\Review;
use App\Review\Domain\Exception\ReviewAlreadyExistsException;
use App\Review\Domain\Interfaces\ReviewRepositoryInterface;
use App\Review\Domain\ValueObject\ReviewBody;
use App\Review\Domain\ValueObject\ReviewRating;
use App\Shared\Domain\ValueObject\Uuid;

class CreateReview
{
    public function __construct(
        private ReviewRepositoryInterface $reviewRepository,
    ) {}

    public function __invoke(
        string $userUuid,
        string $movieUuid,
        int $rating,
        ?string $body,
        bool $containsSpoilers,
    ): CreateReviewResponse {
        $userId = Uuid::create($userUuid);
        $movieId = Uuid::create($movieUuid);

        if ($this->reviewRepository->findByUserAndMovie($userId, $movieId) !== null) {
            throw ReviewAlreadyExistsException::forUserAndMovie($userId, $movieId);
        }

        $review = Review::dddCreate(
            $userId,
            $movieId,
            ReviewRating::create($rating),
            $body !== null ? ReviewBody::create($body) : null,
            $containsSpoilers,
        );

        $this->reviewRepository->create($review);

        return CreateReviewResponse::create($review);
    }
}
