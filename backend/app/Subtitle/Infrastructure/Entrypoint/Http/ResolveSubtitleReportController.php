<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Entrypoint\Http;

use App\Subtitle\Application\ResolveSubtitleReport\ResolveSubtitleReport;
use App\Subtitle\Infrastructure\Entrypoint\Http\Requests\ResolveSubtitleReportRequest;
use Illuminate\Http\JsonResponse;

class ResolveSubtitleReportController
{
    public function __construct(private ResolveSubtitleReport $resolveSubtitleReport) {}

    public function __invoke(ResolveSubtitleReportRequest $request, int $reportId): JsonResponse
    {
        $response = ($this->resolveSubtitleReport)(
            $reportId,
            (string) $request->validated('decision'),
        );

        return new JsonResponse($response->toArray());
    }
}
