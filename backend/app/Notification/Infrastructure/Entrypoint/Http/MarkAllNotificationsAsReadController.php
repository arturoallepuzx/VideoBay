<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Entrypoint\Http;

use App\Notification\Application\MarkAllNotificationsAsRead\MarkAllNotificationsAsRead;
use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use Illuminate\Http\JsonResponse;

class MarkAllNotificationsAsReadController
{
    public function __construct(
        private MarkAllNotificationsAsRead $markAllNotificationsAsRead,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        ($this->markAllNotificationsAsRead)($context->userId()->value());

        return new JsonResponse(null, 204);
    }
}
