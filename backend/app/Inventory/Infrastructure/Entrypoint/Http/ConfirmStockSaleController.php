<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http;

use App\Inventory\Application\ConfirmStockSale\ConfirmStockSale;
use App\Inventory\Infrastructure\Entrypoint\Http\Requests\StockOperationRequest;
use Illuminate\Http\JsonResponse;

class ConfirmStockSaleController
{
    public function __construct(
        private ConfirmStockSale $confirmStockSale,
    ) {}

    public function __invoke(StockOperationRequest $request, string $copyId): JsonResponse
    {
        $response = ($this->confirmStockSale)(
            $copyId,
            $request->validated('quantity'),
        );

        return new JsonResponse($response->toArray());
    }
}
