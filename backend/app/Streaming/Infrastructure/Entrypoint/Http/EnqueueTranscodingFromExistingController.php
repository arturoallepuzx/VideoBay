<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http;

use App\Streaming\Application\EnqueueTranscodingFromExisting\EnqueueTranscodingFromExisting;
use App\Streaming\Infrastructure\Entrypoint\Http\Requests\EnqueueTranscodingFromExistingRequest;
use Illuminate\Http\JsonResponse;

class EnqueueTranscodingFromExistingController
{
    public function __construct(
        private EnqueueTranscodingFromExisting $enqueueTranscodingFromExisting,
    ) {}

    public function __invoke(EnqueueTranscodingFromExistingRequest $request): JsonResponse
    {
        $tmdbId = $request->validated('tmdb_id');

        $response = ($this->enqueueTranscodingFromExisting)(
            $request->validated('source_filename'),
            $request->validated('movie_uuid'),
            $tmdbId !== null ? (int) $tmdbId : null,
        );

        return new JsonResponse($response->toArray(), 202);
    }
}
