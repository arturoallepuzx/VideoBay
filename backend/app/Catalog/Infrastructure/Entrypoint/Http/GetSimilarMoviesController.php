<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Entrypoint\Http;

use App\Catalog\Application\GetSimilarMovies\GetSimilarMovies;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetSimilarMoviesController
{
    public function __construct(
        private GetSimilarMovies $getSimilarMovies,
    ) {}

    public function __invoke(Request $request, string $identifier): JsonResponse
    {
        $page = (int) $request->query('page', 1);

        $response = ($this->getSimilarMovies)($identifier, $page);

        return new JsonResponse($response->toArray());
    }
}
