<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\CreateCheckoutSession\CreateCheckoutSession;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\JsonResponse;

class CreateCheckoutSessionController
{
    public function __construct(
        private CreateCheckoutSession $createCheckoutSession,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->createCheckoutSession)($context->userId()->value());

        return new JsonResponse($response->toArray(), 201);
    }
}
