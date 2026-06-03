<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\Subtitle\Application\ImportExternalSubtitle\ImportExternalSubtitle;
use App\Subtitle\Infrastructure\Entrypoint\Http\Requests\ImportExternalSubtitleRequest;
use Illuminate\Http\JsonResponse;

class ImportExternalSubtitleController
{
    public function __construct(
        private ImportExternalSubtitle $importExternalSubtitle,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(ImportExternalSubtitleRequest $request, string $movieId): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->importExternalSubtitle)(
            $movieId,
            $context->userId()->value(),
            (string) $request->validated('provider'),
            (string) $request->validated('external_id'),
            (string) $request->validated('file_id'),
            (string) $request->validated('language'),
            (string) $request->validated('label'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
