<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Entrypoint\Http;

use App\Inventory\Application\UpdatePhysicalCopy\UpdatePhysicalCopy;
use App\Inventory\Infrastructure\Entrypoint\Http\Requests\UpdatePhysicalCopyRequest;
use Illuminate\Http\JsonResponse;

class UpdatePhysicalCopyController
{
    public function __construct(
        private UpdatePhysicalCopy $updatePhysicalCopy,
    ) {}

    public function __invoke(UpdatePhysicalCopyRequest $request, string $copyId): JsonResponse
    {
        $validated = $request->validated();

        $response = ($this->updatePhysicalCopy)(
            $copyId,
            $validated['barcode'] ?? null,
            array_key_exists('barcode', $validated),
            $validated['condition'] ?? null,
            $validated['cover_photo_url'] ?? null,
            array_key_exists('cover_photo_url', $validated),
            $validated['price_cents'] ?? null,
            $validated['active'] ?? null,
        );

        return new JsonResponse($response->toArray());
    }
}
