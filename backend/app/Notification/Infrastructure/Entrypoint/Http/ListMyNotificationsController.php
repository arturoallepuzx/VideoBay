<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Entrypoint\Http;

use App\Notification\Application\ListMyNotifications\ListMyNotifications;
use App\Notification\Infrastructure\Entrypoint\Http\Requests\ListMyNotificationsRequest;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\JsonResponse;

class ListMyNotificationsController
{
    public function __construct(
        private ListMyNotifications $listMyNotifications,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(ListMyNotificationsRequest $request): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->listMyNotifications)(
            $context->userId()->value(),
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', 0),
        );

        return new JsonResponse($response->toArray());
    }
}
