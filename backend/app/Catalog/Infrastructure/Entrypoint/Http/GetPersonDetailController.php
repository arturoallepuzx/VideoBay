<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Entrypoint\Http;

use App\Catalog\Application\GetPersonDetail\GetPersonDetail;
use Illuminate\Http\JsonResponse;

class GetPersonDetailController
{
    public function __construct(
        private GetPersonDetail $getPersonDetail,
    ) {}

    public function __invoke(string $identifier): JsonResponse
    {
        $response = ($this->getPersonDetail)($identifier);

        return new JsonResponse($response->toArray());
    }
}
