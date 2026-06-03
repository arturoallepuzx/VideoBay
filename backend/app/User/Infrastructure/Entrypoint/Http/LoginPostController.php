<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\LoginUser\LoginUser;
use App\User\Application\LoginUser\LoginUserResponse;
use App\User\Infrastructure\Entrypoint\Http\Requests\LoginUserRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;

class LoginPostController
{
    public function __construct(
        private LoginUser $loginUser,
    ) {}

    public function __invoke(LoginUserRequest $request): JsonResponse
    {
        $response = ($this->loginUser)(
            $request->validated('email'),
            $request->validated('password'),
        );

        return (new JsonResponse($response->toArray(), 200))
            ->withCookie($this->buildAccessCookie($response))
            ->withCookie($this->buildRefreshCookie($response));
    }

    private function buildAccessCookie(LoginUserResponse $response): Cookie
    {
        return Cookie::create('access_token')
            ->withValue($response->accessToken())
            ->withExpires($response->accessTokenExpiresAt()->value())
            ->withPath('/api')
            ->withSecure(app()->environment('production'))
            ->withHttpOnly(true)
            ->withSameSite('lax');
    }

    private function buildRefreshCookie(LoginUserResponse $response): Cookie
    {
        return Cookie::create('refresh_token')
            ->withValue($response->refreshToken())
            ->withExpires($response->refreshTokenExpiresAt()->value())
            ->withPath('/api/auth')
            ->withSecure(app()->environment('production'))
            ->withHttpOnly(true)
            ->withSameSite('lax');
    }
}
