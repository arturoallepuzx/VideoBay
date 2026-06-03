<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http;

use App\Inventory\Application\ReleaseReservedStock\ReleaseReservedStock;
use App\Inventory\Infrastructure\Entrypoint\Http\Requests\StockOperationRequest;
use Illuminate\Http\JsonResponse;

class ReleaseReservedStockController
{
    public function __construct(
        private ReleaseReservedStock $releaseReservedStock,
    ) {}

    public function __invoke(StockOperationRequest $request, string $copyId): JsonResponse
    {
        $response = ($this->releaseReservedStock)(
            $copyId,
            $request->validated('quantity'),
        );

        return new JsonResponse($response->toArray());
    }
}
