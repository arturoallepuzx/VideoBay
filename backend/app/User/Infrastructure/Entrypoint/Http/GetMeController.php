<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\User\Application\GetAuthenticatedUser\GetAuthenticatedUser;
use Illuminate\Http\JsonResponse;

class GetMeController
{
    public function __construct(
        private GetAuthenticatedUser $getAuthenticatedUser,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->getAuthenticatedUser)($context->userId()->value());

        return new JsonResponse($response->toArray(), 200);
    }
}
