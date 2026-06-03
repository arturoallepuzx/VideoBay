<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\GetUserById\GetUserById;
use Illuminate\Http\JsonResponse;

class GetByIdController
{
    public function __construct(
        private GetUserById $getUserById,
    ) {}

    public function __invoke(string $userId): JsonResponse
    {
        $response = ($this->getUserById)($userId);

        return new JsonResponse($response->toArray());
    }
}
