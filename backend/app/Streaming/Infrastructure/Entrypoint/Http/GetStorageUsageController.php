<?php

declare(strict_types=1);

namespace App\Streaming\Infrastructure\Entrypoint\Http;

use App\Streaming\Application\GetStorageUsage\GetStorageUsage;
use Illuminate\Http\JsonResponse;

class GetStorageUsageController
{
    public function __construct(
        private GetStorageUsage $getStorageUsage,
    ) {}

    public function __invoke(): JsonResponse
    {
        return new JsonResponse(($this->getStorageUsage)()->toArray());
    }
}
