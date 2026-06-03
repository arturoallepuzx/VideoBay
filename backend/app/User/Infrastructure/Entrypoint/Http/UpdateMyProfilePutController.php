<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\User\Application\UpdateMyProfile\UpdateMyProfile;
use App\User\Infrastructure\Entrypoint\Http\Requests\UpdateMyProfileRequest;
use Illuminate\Http\JsonResponse;

class UpdateMyProfilePutController
{
    public function __construct(
        private UpdateMyProfile $updateMyProfile,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(UpdateMyProfileRequest $request): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $validated = $request->validated();

        $response = ($this->updateMyProfile)(
            $context->userId()->value(),
            $validated['name'] ?? null,
            $validated['email'] ?? null,
            $validated['avatar_url'] ?? null,
            array_key_exists('avatar_url', $validated),
        );

        return new JsonResponse($response->toArray());
    }
}
