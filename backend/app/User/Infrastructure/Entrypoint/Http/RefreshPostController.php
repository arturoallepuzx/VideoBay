<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\RefreshAuthentication\RefreshAuthentication;
use App\User\Application\RefreshAuthentication\RefreshAuthenticationResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Cookie;

class RefreshPostController
{
    public function __construct(
        private RefreshAuthentication $refreshAuthentication,
    ) {}

    public function __invoke(Request $request): Response
    {
        $refreshCredential = (string) $request->cookies->get('refresh_token', '');

        $response = ($this->refreshAuthentication)($refreshCredential);

        return (new Response('', 204))
            ->withCookie($this->buildAccessCookie($response))
            ->withCookie($this->buildRefreshCookie($response));
    }

    private function buildAccessCookie(RefreshAuthenticationResponse $response): Cookie
    {
        return Cookie::create('access_token')
            ->withValue($response->accessToken())
            ->withExpires($response->accessTokenExpiresAt()->value())
            ->withPath('/api')
            ->withSecure(app()->environment('production'))
            ->withHttpOnly(true)
            ->withSameSite('lax');
    }

    private function buildRefreshCookie(RefreshAuthenticationResponse $response): Cookie
    {
        return Cookie::create('refresh_token')
            ->withValue($response->refreshCredential())
            ->withExpires($response->refreshCredentialExpiresAt()->value())
            ->withPath('/api/auth')
            ->withSecure(app()->environment('production'))
            ->withHttpOnly(true)
            ->withSameSite('lax');
    }
}
