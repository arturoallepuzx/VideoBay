<?php

declare(strict_types=1);

namespace App\Review\Application\ListMovieReviews;

use App\Review\Domain\Entity\Review;
use App\Review\Domain\Interfaces\ReviewRepositoryInterface;
use App\Review\Domain\Interfaces\UserDisplayResolverInterface;
use App\Shared\Domain\ValueObject\Uuid;

class ListMovieReviews
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private ReviewRepositoryInterface $reviewRepository,
        private UserDisplayResolverInterface $userDisplayResolver,
    ) {}

    public function __invoke(string $movieUuid, int $page, int $perPage): ListMovieReviewsResponse
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE));

        $result = $this->reviewRepository->listByMovie(Uuid::create($movieUuid), $page, $perPage);

        $authorIds = $this->collectUniqueUserIds($result['items']);
        $authorDisplays = $this->userDisplayResolver->resolveMany($authorIds);

        return ListMovieReviewsResponse::create($result, $authorDisplays);
    }

    /**
     * @param  list<Review>  $reviews
     * @return list<Uuid>
     */
    private function collectUniqueUserIds(array $reviews): array
    {
        $seen = [];
        $ids = [];

        foreach ($reviews as $review) {
            $value = $review->userId()->value();
            if (isset($seen[$value])) {
                continue;
            }
            $seen[$value] = true;
            $ids[] = $review->userId();
        }

        return $ids;
    }
}
