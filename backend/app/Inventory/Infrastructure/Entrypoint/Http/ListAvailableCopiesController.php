<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http;

use App\Inventory\Application\ListAvailableCopies\ListAvailableCopies;
use App\Inventory\Infrastructure\Entrypoint\Http\Requests\ListAvailableCopiesRequest;
use Illuminate\Http\JsonResponse;

class ListAvailableCopiesController
{
    public function __construct(
        private ListAvailableCopies $listAvailableCopies,
    ) {}

    public function __invoke(ListAvailableCopiesRequest $request): JsonResponse
    {
        $response = ($this->listAvailableCopies)(
            $request->validated('movie_id'),
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', 0),
        );

        return new JsonResponse($response->toArray());
    }
}
