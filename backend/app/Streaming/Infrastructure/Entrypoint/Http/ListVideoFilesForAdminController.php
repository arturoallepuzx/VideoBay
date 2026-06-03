<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http;

use App\Streaming\Application\ListVideoFilesForAdmin\ListVideoFilesForAdmin;
use App\Streaming\Infrastructure\Entrypoint\Http\Requests\ListVideoFilesForAdminRequest;
use Illuminate\Http\JsonResponse;

class ListVideoFilesForAdminController
{
    public function __construct(
        private ListVideoFilesForAdmin $listVideoFilesForAdmin,
    ) {}

    public function __invoke(ListVideoFilesForAdminRequest $request): JsonResponse
    {
        $response = ($this->listVideoFilesForAdmin)(
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', 0),
        );

        return new JsonResponse($response->toArray());
    }
}
