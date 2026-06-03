<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Entrypoint\Http;

use App\Catalog\Application\ResolveBarcode\ResolveBarcode;
use App\Catalog\Infrastructure\Entrypoint\Http\Requests\ResolveBarcodeRequest;
use Illuminate\Http\JsonResponse;

class ResolveBarcodeController
{
    public function __construct(
        private ResolveBarcode $resolveBarcode,
    ) {}

    public function __invoke(ResolveBarcodeRequest $request): JsonResponse
    {
        $response = ($this->resolveBarcode)((string) $request->validated('barcode'));

        return new JsonResponse($response->toArray());
    }
}
