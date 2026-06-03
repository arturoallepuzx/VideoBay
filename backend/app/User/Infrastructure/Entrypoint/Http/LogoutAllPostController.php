<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\User\Application\LogoutAllUserSessions\LogoutAllUserSessions;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Cookie;

class LogoutAllPostController
{
    public function __construct(
        private LogoutAllUserSessions $logoutAllUserSessions,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(): Response
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        ($this->logoutAllUserSessions)($context->userId()->value());

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
