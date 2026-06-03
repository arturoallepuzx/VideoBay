<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Entrypoint\Http;

use App\Review\Application\ResolveReviewReport\ResolveReviewReport;
use App\Review\Infrastructure\Entrypoint\Http\Requests\ResolveReviewReportRequest;
use Illuminate\Http\JsonResponse;

class ResolveReviewReportController
{
    public function __construct(
        private ResolveReviewReport $resolveReviewReport,
    ) {}

    public function __invoke(ResolveReviewReportRequest $request, int $reportId): JsonResponse
    {
        $response = ($this->resolveReviewReport)(
            $reportId,
            (string) $request->validated('decision'),
        );

        return new JsonResponse($response->toArray());
    }
}
