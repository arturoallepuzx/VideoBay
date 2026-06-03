<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\LogoutUser\LogoutUser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Cookie;

class LogoutPostController
{
    public function __construct(
        private LogoutUser $logoutUser,
    ) {}

    public function __invoke(Request $request): Response
    {
        $refreshCredential = (string) $request->cookies->get('refresh_token', '');

        ($this->logoutUser)($refreshCredential);

        return (new Response('', 204))
            ->withCookie($this->buildClearedAccessCookie())
            ->withCookie($this->buildClearedRefreshCookie());
    }

    private function buildClearedAccessCookie(): Cookie
    {
        return Cookie::create('access_token')
            ->withValue('')
            ->withExpires((new \DateTimeImmutable)->modify('-1 day'))
            ->withPath('/api')
            ->withSecure(app()->environment('production'))
            ->withHttpOnly(true)
            ->withSameSite('lax');
    }

    private function buildClearedRefreshCookie(): Cookie
    {
        return Cookie::create('refresh_token')
            ->withValue('')
            ->withExpires((new \DateTimeImmutable)->modify('-1 day'))
            ->withPath('/api/auth')
            ->withSecure(app()->environment('production'))
            ->withHttpOnly(true)
            ->withSameSite('lax');
    }
}
