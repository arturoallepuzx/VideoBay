<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Entrypoint\Http;

use App\Catalog\Application\GetMovieDetail\GetMovieDetail;
use Illuminate\Http\JsonResponse;

class GetMovieDetailController
{
    public function __construct(
        private GetMovieDetail $getMovieDetail,
    ) {}

    public function __invoke(string $identifier): JsonResponse
    {
        $response = ($this->getMovieDetail)($identifier);

        return new JsonResponse($response->toArray());
    }
}
