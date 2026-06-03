<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\ForceLogoutUser\ForceLogoutUser;
use Illuminate\Http\Response;

class ForceLogoutPostController
{
    public function __construct(
        private ForceLogoutUser $forceLogoutUser,
    ) {}

    public function __invoke(string $userId): Response
    {
        ($this->forceLogoutUser)($userId);

        return new Response('', 204);
    }
}
