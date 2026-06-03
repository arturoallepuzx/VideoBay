<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http;

use App\Streaming\Application\ReassignVideoFile\ReassignVideoFile;
use App\Streaming\Infrastructure\Entrypoint\Http\Requests\ReassignVideoFileRequest;
use Illuminate\Http\JsonResponse;

class ReassignVideoFileController
{
    public function __construct(
        private ReassignVideoFile $reassignVideoFile,
    ) {}

    public function __invoke(ReassignVideoFileRequest $request, string $videoFileId): JsonResponse
    {
        $tmdbId = $request->validated('tmdb_id');

        $response = ($this->reassignVideoFile)(
            $videoFileId,
            $request->validated('movie_uuid'),
            $tmdbId !== null ? (int) $tmdbId : null,
        );

        return new JsonResponse($response->toArray());
    }
}
