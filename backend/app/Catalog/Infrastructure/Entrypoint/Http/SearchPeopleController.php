<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Entrypoint\Http;

use App\Catalog\Application\SearchPeople\SearchPeople;
use App\Catalog\Infrastructure\Entrypoint\Http\Requests\SearchCatalogRequest;
use Illuminate\Http\JsonResponse;

class SearchPeopleController
{
    public function __construct(
        private SearchPeople $searchPeople,
    ) {}

    public function __invoke(SearchCatalogRequest $request): JsonResponse
    {
        $response = ($this->searchPeople)(
            (string) $request->validated('query'),
            (int) $request->validated('page', 1),
        );

        return new JsonResponse($response->toArray());
    }
}
