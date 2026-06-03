<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EnsureXsrfCookie
{
    private const COOKIE_NAME = 'XSRF-TOKEN';

    private const TOKEN_BYTES = 32;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->cookies->has(self::COOKIE_NAME)) {
            return $response;
        }

        $token = $this->generateToken();

        $response->headers->setCookie($this->buildCookie($token));

        return $response;
    }

    private function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(self::TOKEN_BYTES)), '+/', '-_'), '=');
    }

    private function buildCookie(string $token): Cookie
    {
        return Cookie::create(self::COOKIE_NAME)
            ->withValue($token)
            ->withPath('/')
            ->withSecure(app()->environment('production'))
            ->withHttpOnly(false)
            ->withSameSite('lax');
    }
}
