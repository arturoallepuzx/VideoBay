<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Entrypoint\Http;

use App\Catalog\Application\ListPersonFilmography\ListPersonFilmography;
use Illuminate\Http\JsonResponse;

class ListPersonFilmographyController
{
    public function __construct(
        private ListPersonFilmography $listPersonFilmography,
    ) {}

    public function __invoke(string $identifier): JsonResponse
    {
        $response = ($this->listPersonFilmography)($identifier);

        return new JsonResponse($response->toArray());
    }
}
