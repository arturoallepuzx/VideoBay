<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http;

use App\Streaming\Application\RegisterExistingVideoFile\RegisterExistingVideoFile;
use App\Streaming\Infrastructure\Entrypoint\Http\Requests\RegisterExistingVideoFileRequest;
use Illuminate\Http\JsonResponse;

class RegisterExistingVideoFileController
{
    public function __construct(
        private RegisterExistingVideoFile $registerExistingVideoFile,
    ) {}

    public function __invoke(RegisterExistingVideoFileRequest $request): JsonResponse
    {
        $tmdbId = $request->validated('tmdb_id');

        $response = ($this->registerExistingVideoFile)(
            $request->validated('source_filename'),
            $request->validated('movie_uuid'),
            $tmdbId !== null ? (int) $tmdbId : null,
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
