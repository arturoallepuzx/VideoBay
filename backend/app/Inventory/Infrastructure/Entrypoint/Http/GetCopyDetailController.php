<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http;

use App\Inventory\Application\GetCopyDetail\GetCopyDetail;
use Illuminate\Http\JsonResponse;

class GetCopyDetailController
{
    public function __construct(
        private GetCopyDetail $getCopyDetail,
    ) {}

    public function __invoke(string $copyId): JsonResponse
    {
        $response = ($this->getCopyDetail)($copyId);

        return new JsonResponse($response->toArray());
    }
}
