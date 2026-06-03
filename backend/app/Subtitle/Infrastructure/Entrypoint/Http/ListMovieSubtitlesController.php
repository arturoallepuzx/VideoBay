<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Entrypoint\Http;

use App\Subtitle\Application\ListMovieSubtitles\ListMovieSubtitles;
use Illuminate\Http\JsonResponse;

class ListMovieSubtitlesController
{
    public function __construct(private ListMovieSubtitles $listMovieSubtitles) {}

    public function __invoke(string $movieId): JsonResponse
    {
        $response = ($this->listMovieSubtitles)($movieId);

        return new JsonResponse($response->toArray());
    }
}
