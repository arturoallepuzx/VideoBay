<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Entrypoint\Http;

use App\Catalog\Application\ListStreamableMovies\ListStreamableMovies;
use App\Catalog\Infrastructure\Entrypoint\Http\Requests\MovieCatalogFilterRequest;
use Illuminate\Http\JsonResponse;

class ListStreamableMoviesController
{
    public function __construct(
        private ListStreamableMovies $listStreamableMovies,
    ) {}

    public function __invoke(MovieCatalogFilterRequest $request): JsonResponse
    {
        $yearFrom = $request->validated('year_from');
        $yearTo = $request->validated('year_to');

        $response = ($this->listStreamableMovies)(
            $request->validated('genre'),
            $yearFrom !== null ? (int) $yearFrom : null,
            $yearTo !== null ? (int) $yearTo : null,
            $request->validated('sort'),
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', 0),
        );

        return new JsonResponse($response->toArray());
    }
}
