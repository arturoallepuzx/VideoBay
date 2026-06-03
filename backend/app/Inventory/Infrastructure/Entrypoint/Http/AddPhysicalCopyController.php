<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http;

use App\Inventory\Application\AddPhysicalCopy\AddPhysicalCopy;
use App\Inventory\Infrastructure\Entrypoint\Http\Requests\AddPhysicalCopyRequest;
use Illuminate\Http\JsonResponse;

class AddPhysicalCopyController
{
    public function __construct(
        private AddPhysicalCopy $addPhysicalCopy,
    ) {}

    public function __invoke(AddPhysicalCopyRequest $request): JsonResponse
    {
        $response = ($this->addPhysicalCopy)(
            $request->validated('movie_id'),
            $request->validated('sku'),
            $request->validated('barcode'),
            $request->validated('format'),
            $request->validated('region'),
            $request->validated('condition'),
            $request->validated('cover_photo_url'),
            $request->validated('price_cents'),
            $request->validated('stock_available'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
