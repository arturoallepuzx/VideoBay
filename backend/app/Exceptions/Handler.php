<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Shared\Domain\Exception\ConflictException;
use App\Shared\Domain\Exception\ForbiddenException;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\Exception\ServiceUnavailableException;
use App\Shared\Domain\Exception\UnauthorizedException;
use App\Shared\Domain\Exception\ValidationException as DomainValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class Handler
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (ValidationException $e) {
            return new JsonResponse(['error' => 'The given data was invalid.', 'details' => $e->errors()], 422);
        });

        $exceptions->render(function (NotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        });

        $exceptions->render(function (ModelNotFoundException $e) {
            return new JsonResponse(['error' => 'Resource not found.'], 404);
        });

        $exceptions->render(function (ConflictException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        });

        $exceptions->render(function (UnauthorizedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        });

        $exceptions->render(function (ForbiddenException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 403);
        });

        $exceptions->render(function (DomainValidationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 422);
        });

        $exceptions->render(function (ServiceUnavailableException $e) {
            return new JsonResponse(['error' => 'External service unavailable.'], 503);
        });

        $exceptions->render(function (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 422);
        });
    }
}
