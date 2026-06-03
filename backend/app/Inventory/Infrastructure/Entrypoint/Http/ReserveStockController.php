<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http;

use App\Inventory\Application\ReserveStock\ReserveStock;
use App\Inventory\Infrastructure\Entrypoint\Http\Requests\StockOperationRequest;
use Illuminate\Http\JsonResponse;

class ReserveStockController
{
    public function __construct(
        private ReserveStock $reserveStock,
    ) {}

    public function __invoke(StockOperationRequest $request, string $copyId): JsonResponse
    {
        $response = ($this->reserveStock)(
            $copyId,
            $request->validated('quantity'),
        );

        return new JsonResponse($response->toArray());
    }
}
