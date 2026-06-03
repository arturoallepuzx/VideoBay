<?php

declare(strict_types=1);

namespace App\User\Application\UpdateMyAccessibilitySettings;

use App\User\Domain\Entity\User;

final readonly class UpdateMyAccessibilitySettingsResponse
{
    /** @param array<string, mixed> $accessibilitySettings */
    public function __construct(
        public array $accessibilitySettings,
    ) {}

    public static function create(User $user): self
    {
        return new self(
            accessibilitySettings: $user->accessibilitySettings()->toArray(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'accessibility_settings' => $this->accessibilitySettings,
        ];
    }
}
