<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\CreateUser\CreateUser;
use App\User\Infrastructure\Entrypoint\Http\Requests\CreateUserRequest;
use Illuminate\Http\JsonResponse;

class PostController
{
    public function __construct(
        private CreateUser $createUser,
    ) {}

    public function __invoke(CreateUserRequest $request): JsonResponse
    {
        $response = ($this->createUser)(
            $request->validated('role'),
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('avatar_url'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
