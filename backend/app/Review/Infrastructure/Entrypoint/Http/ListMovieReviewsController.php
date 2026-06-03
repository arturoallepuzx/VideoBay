<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Entrypoint\Http;

use App\Review\Application\ListMovieReviews\ListMovieReviews;
use App\Review\Infrastructure\Entrypoint\Http\Requests\ListReviewsRequest;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\JsonResponse;

class ListMovieReviewsController
{
    public function __construct(
        private ListMovieReviews $listMovieReviews,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(ListReviewsRequest $request, string $movieId): JsonResponse
    {
        $viewerUuid = $this->authContextHolder->get()?->userId()->value();

        $response = ($this->listMovieReviews)(
            $movieId,
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', 0),
            $viewerUuid,
        );

        return new JsonResponse($response->toArray());
    }
}
