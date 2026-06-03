<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Entrypoint\Http;

use App\Subtitle\Application\SearchExternalSubtitles\SearchExternalSubtitles;
use App\Subtitle\Infrastructure\Entrypoint\Http\Requests\SearchExternalSubtitlesRequest;
use Illuminate\Http\JsonResponse;

class SearchExternalSubtitlesController
{
    public function __construct(private SearchExternalSubtitles $searchExternalSubtitles) {}

    public function __invoke(SearchExternalSubtitlesRequest $request, string $movieId): JsonResponse
    {
        $response = ($this->searchExternalSubtitles)(
            $movieId,
            $request->validated('language'),
        );

        return new JsonResponse($response->toArray());
    }
}
