<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http;

use App\Streaming\Application\ListPendingVideoFiles\ListPendingVideoFiles;
use Illuminate\Http\JsonResponse;

class ListPendingVideoFilesController
{
    public function __construct(
        private ListPendingVideoFiles $listPendingVideoFiles,
    ) {}

    public function __invoke(): JsonResponse
    {
        $response = ($this->listPendingVideoFiles)();

        return new JsonResponse($response->toArray());
    }
}
