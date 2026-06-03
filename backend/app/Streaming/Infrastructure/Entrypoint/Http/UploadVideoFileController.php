<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http;

use App\Streaming\Application\UploadVideoFile\UploadVideoFile;
use App\Streaming\Infrastructure\Entrypoint\Http\Requests\UploadVideoFileRequest;
use Illuminate\Http\JsonResponse;

class UploadVideoFileController
{
    public function __construct(
        private UploadVideoFile $uploadVideoFile,
    ) {}

    public function __invoke(UploadVideoFileRequest $request): JsonResponse
    {
        $file = $request->file('video');
        $tmdbId = $request->validated('tmdb_id');

        $response = ($this->uploadVideoFile)(
            $file->getPathname(),
            $file->getClientOriginalName(),
            $request->validated('movie_uuid'),
            $tmdbId !== null ? (int) $tmdbId : null,
        );

        return new JsonResponse($response->toArray(), 202);
    }
}
