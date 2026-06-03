<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\UpdateUser\UpdateUser;
use App\User\Infrastructure\Entrypoint\Http\Requests\UpdateUserRequest;
use Illuminate\Http\JsonResponse;

class PutController
{
    public function __construct(
        private UpdateUser $updateUser,
    ) {}

    public function __invoke(UpdateUserRequest $request, string $userId): JsonResponse
    {
        $validated = $request->validated();

        $response = ($this->updateUser)(
            $userId,
            $validated['name'] ?? null,
            $validated['email'] ?? null,
            $validated['role'] ?? null,
            $validated['avatar_url'] ?? null,
            array_key_exists('avatar_url', $validated),
        );

        return new JsonResponse($response->toArray());
    }
}
