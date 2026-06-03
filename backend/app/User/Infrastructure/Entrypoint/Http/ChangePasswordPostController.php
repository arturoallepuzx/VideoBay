<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\User\Application\ChangePassword\ChangePassword;
use App\User\Infrastructure\Entrypoint\Http\Requests\ChangePasswordRequest;
use Illuminate\Http\Response;

class ChangePasswordPostController
{
    public function __construct(
        private ChangePassword $changePassword,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(ChangePasswordRequest $request): Response
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        ($this->changePassword)(
            $context->userId()->value(),
            $request->validated('current_password'),
            $request->validated('password'),
        );

        return new Response('', 204);
    }
}
