<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Entrypoint\Http;

use App\Review\Application\ListUserReviews\ListUserReviews;
use App\Review\Infrastructure\Entrypoint\Http\Requests\ListReviewsRequest;
use Illuminate\Http\JsonResponse;

class ListUserReviewsController
{
    public function __construct(
        private ListUserReviews $listUserReviews,
    ) {}

    public function __invoke(ListReviewsRequest $request, string $userId): JsonResponse
    {
        $response = ($this->listUserReviews)(
            $userId,
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', 0),
        );

        return new JsonResponse($response->toArray());
    }
}
