<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Entrypoint\Http;

use App\Notification\Application\MarkNotificationAsRead\MarkNotificationAsRead;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\JsonResponse;

class MarkNotificationAsReadController
{
    public function __construct(
        private MarkNotificationAsRead $markNotificationAsRead,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(string $notificationId): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        ($this->markNotificationAsRead)($context->userId()->value(), $notificationId);

        return new JsonResponse(null, 204);
    }
}
