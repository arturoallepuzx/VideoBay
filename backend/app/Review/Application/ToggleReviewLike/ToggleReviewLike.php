<?php

declare(strict_types=1);

namespace App\Review\Application\ToggleReviewLike;

use App\Review\Domain\Entity\ReviewLike;
use App\Review\Domain\Event\ReviewLiked;
use App\Review\Domain\Exception\ReviewNotFoundException;
use App\Review\Domain\Interfaces\ReviewLikeRepositoryInterface;
use App\Review\Domain\Interfaces\ReviewRepositoryInterface;
use App\Shared\Domain\Interfaces\DomainEventDispatcherInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;

class ToggleReviewLike
{
    public function __construct(
        private ReviewRepositoryInterface $reviewRepository,
        private ReviewLikeRepositoryInterface $likeRepository,
        private TransactionRunnerInterface $transactionRunner,
        private DomainEventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(string $reviewUuid, string $userUuid): ToggleReviewLikeResponse
    {
        $reviewId = Uuid::create($reviewUuid);
        $userId = Uuid::create($userUuid);

        /** @var array{liked: bool, likesCount: int} $result */
        $result = $this->transactionRunner->run(function () use ($reviewId, $userId): array {
            $review = $this->reviewRepository->findByUuidForUpdate($reviewId);

            if ($review === null || $review->isDeleted()) {
                throw ReviewNotFoundException::forUuid($reviewId);
            }

            $isLiked = $this->likeRepository->exists($reviewId, $userId);

            if ($isLiked) {
                $this->likeRepository->remove($reviewId, $userId);
                $review->decrementLikes();
                $liked = false;
            } else {
                $this->likeRepository->add(ReviewLike::dddCreate($reviewId, $userId));
                $review->incrementLikes();
                $liked = true;
            }

            if ($review->wasModified()) {
                $this->reviewRepository->update($review);
            }

            if ($liked && ! $review->isOwnedBy($userId)) {
                $this->eventDispatcher->dispatch(ReviewLiked::create(
                    $review->id(),
                    $review->userId(),
                    $userId,
                    $review->movieId(),
                    $review->body()?->value(),
                ));
            }

            return ['liked' => $liked, 'likesCount' => $review->likesCount()];
        });

        return ToggleReviewLikeResponse::create($reviewUuid, $result['liked'], $result['likesCount']);
    }
}
