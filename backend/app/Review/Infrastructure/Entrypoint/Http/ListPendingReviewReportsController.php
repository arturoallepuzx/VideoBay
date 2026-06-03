<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Entrypoint\Http;

use App\Review\Application\ListPendingReviewReports\ListPendingReviewReports;
use App\Review\Infrastructure\Entrypoint\Http\Requests\ListPendingReviewReportsRequest;
use Illuminate\Http\JsonResponse;

class ListPendingReviewReportsController
{
    public function __construct(
        private ListPendingReviewReports $listPendingReviewReports,
    ) {}

    public function __invoke(ListPendingReviewReportsRequest $request): JsonResponse
    {
        $response = ($this->listPendingReviewReports)(
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', 0),
        );

        return new JsonResponse($response->toArray());
    }
}
