<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Entrypoint\Http;

use App\Catalog\Application\SearchMovies\SearchMovies;
use App\Catalog\Infrastructure\Entrypoint\Http\Requests\SearchCatalogRequest;
use Illuminate\Http\JsonResponse;

class SearchMoviesController
{
    public function __construct(
        private SearchMovies $searchMovies,
    ) {}

    public function __invoke(SearchCatalogRequest $request): JsonResponse
    {
        $response = ($this->searchMovies)(
            (string) $request->validated('query'),
            (int) $request->validated('page', 1),
        );

        return new JsonResponse($response->toArray());
    }
}
