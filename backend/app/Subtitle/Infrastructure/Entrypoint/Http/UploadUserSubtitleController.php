<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\Subtitle\Application\UploadUserSubtitle\UploadUserSubtitle;
use App\Subtitle\Infrastructure\Entrypoint\Http\Requests\UploadUserSubtitleRequest;
use Illuminate\Http\JsonResponse;

class UploadUserSubtitleController
{
    public function __construct(
        private UploadUserSubtitle $uploadUserSubtitle,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(UploadUserSubtitleRequest $request, string $movieId): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $file = $request->file('subtitle');
        $response = ($this->uploadUserSubtitle)(
            $movieId,
            $context->userId()->value(),
            $context->isAdmin(),
            $file->getPathname(),
            $file->getClientOriginalName(),
            (string) $request->validated('language'),
            $request->validated('label'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
