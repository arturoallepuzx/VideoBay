<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\ListUsersWithActiveSessions\ListUsersWithActiveSessions;
use Illuminate\Http\JsonResponse;

class GetUsersWithActiveSessionsController
{
    public function __construct(
        private ListUsersWithActiveSessions $listUsersWithActiveSessions,
    ) {}

    public function __invoke(): JsonResponse
    {
        $response = ($this->listUsersWithActiveSessions)();

        return new JsonResponse($response->toArray(), 200);
    }
}
