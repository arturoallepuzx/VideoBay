<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Entrypoint\Http;

use App\Shared\Domain\Exception\AuthenticationRequiredException;
use App\Shared\Infrastructure\Auth\AuthContextHolder;
use App\User\Application\UpdateMyAccessibilitySettings\UpdateMyAccessibilitySettings;
use App\User\Infrastructure\Entrypoint\Http\Requests\UpdateMyAccessibilitySettingsRequest;
use Illuminate\Http\JsonResponse;

class UpdateMyAccessibilitySettingsPutController
{
    public function __construct(
        private UpdateMyAccessibilitySettings $updateMyAccessibilitySettings,
        private AuthContextHolder $authContextHolder,
    ) {}

    public function __invoke(UpdateMyAccessibilitySettingsRequest $request): JsonResponse
    {
        $context = $this->authContextHolder->get();

        if ($context === null) {
            throw AuthenticationRequiredException::missing();
        }

        $response = ($this->updateMyAccessibilitySettings)(
            $context->userId()->value(),
            $request->validated('accessibility_settings'),
        );

        return new JsonResponse($response->toArray());
    }
}
