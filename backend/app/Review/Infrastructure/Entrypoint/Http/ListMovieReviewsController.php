<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Entrypoint\Http;

use App\Review\Application\ListMovieReviews\ListMovieReviews;
use App\Review\Infrastructure\Entrypoint\Http\Requests\ListReviewsRequest;
use Illuminate\Http\JsonResponse;

class ListMovieReviewsController
{
    public function __construct(
        private ListMovieReviews $listMovieReviews,
    ) {}

    public function __invoke(ListReviewsRequest $request, string $movieId): JsonResponse
    {
        $response = ($this->listMovieReviews)(
            $movieId,
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', 0),
        );

        return new JsonResponse($response->toArray());
    }
}
