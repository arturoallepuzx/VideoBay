<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Entrypoint\Http;

use App\Subtitle\Application\ListPendingSubtitleReports\ListPendingSubtitleReports;
use App\Subtitle\Infrastructure\Entrypoint\Http\Requests\ListPendingSubtitleReportsRequest;
use Illuminate\Http\JsonResponse;

class ListPendingSubtitleReportsController
{
    public function __construct(private ListPendingSubtitleReports $listPendingSubtitleReports) {}

    public function __invoke(ListPendingSubtitleReportsRequest $request): JsonResponse
    {
        $response = ($this->listPendingSubtitleReports)(
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', 0),
        );

        return new JsonResponse($response->toArray());
    }
}
