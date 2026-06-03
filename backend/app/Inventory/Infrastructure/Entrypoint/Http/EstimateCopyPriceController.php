<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http;

use App\Inventory\Application\EstimateCopyPrice\EstimateCopyPrice;
use App\Inventory\Infrastructure\Entrypoint\Http\Requests\EstimateCopyPriceRequest;
use Illuminate\Http\JsonResponse;

class EstimateCopyPriceController
{
    public function __construct(
        private EstimateCopyPrice $estimateCopyPrice,
    ) {}

    public function __invoke(EstimateCopyPriceRequest $request): JsonResponse
    {
        $response = ($this->estimateCopyPrice)(
            (string) $request->validated('format'),
            (string) $request->validated('condition'),
        );

        return new JsonResponse($response->toArray());
    }
}
