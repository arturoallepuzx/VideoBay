<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\Subtitle\Application\ReportSubtitle\ReportSubtitle;
use App\Subtitle\Infrastructure\Entrypoint\Http\Requests\ReportSubtitleRequest;
use Illuminate\Http\JsonResponse;

class ReportSubtitleController
{
    public function __construct(
        private ReportSubtitle $reportSubtitle,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(ReportSubtitleRequest $request, string $subtitleId): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->reportSubtitle)(
            $subtitleId,
            $context->userId()->value(),
            (string) $request->validated('reason'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
