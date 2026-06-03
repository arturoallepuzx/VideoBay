<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Http\Middleware;

use App\Auth\Domain\Exception\CsrfTokenMismatchException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCsrfTokenStateless
{
    private const COOKIE_NAME = 'XSRF-TOKEN';

    private const HEADER_NAME = 'X-XSRF-TOKEN';

    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), self::SAFE_METHODS, true)) {
            return $next($request);
        }

        $cookieToken = $request->cookies->get(self::COOKIE_NAME);
        $headerToken = $request->headers->get(self::HEADER_NAME);

        if (! is_string($cookieToken) || $cookieToken === '') {
            throw CsrfTokenMismatchException::missing();
        }

        if (! is_string($headerToken) || $headerToken === '') {
            throw CsrfTokenMismatchException::missing();
        }

        if (! hash_equals($cookieToken, $headerToken)) {
            throw CsrfTokenMismatchException::mismatch();
        }

        return $next($request);
    }
}
