<?php

declare(strict_types=1);

namespace App\User\Application\UpdateMyAccessibilitySettings;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\AccessibilitySettings;

class UpdateMyAccessibilitySettings
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    /** @param array<string, mixed> $settings */
    public function __invoke(
        string $userId,
        array $settings,
    ): UpdateMyAccessibilitySettingsResponse {
        $userUuid = Uuid::create($userId);

        $user = $this->userRepository->findById($userUuid);

        if ($user === null) {
            throw UserNotFoundException::forId($userUuid);
        }

        $user->updateAccessibilitySettings(AccessibilitySettings::create($settings));

        if ($user->wasModified()) {
            $this->userRepository->update($user);
        }

        return UpdateMyAccessibilitySettingsResponse::create($user);
    }
}
