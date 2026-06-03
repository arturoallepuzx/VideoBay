<?php

declare(strict_types=1);

namespace App\Review\Application\ListUserReviews;

use App\Review\Domain\Interfaces\ReviewRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

class ListUserReviews
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private ReviewRepositoryInterface $reviewRepository,
    ) {}

    public function __invoke(string $userUuid, int $page, int $perPage): ListUserReviewsResponse
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE));

        $result = $this->reviewRepository->listByUser(Uuid::create($userUuid), $page, $perPage);

        return ListUserReviewsResponse::create($result);
    }
}
