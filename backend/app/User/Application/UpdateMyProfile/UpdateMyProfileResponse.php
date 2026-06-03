<?php

declare(strict_types=1);

namespace App\User\Application\UpdateMyProfile;

use App\User\Domain\Entity\User;

final readonly class UpdateMyProfileResponse
{
    /** @param array<string, mixed> $accessibilitySettings */
    public function __construct(
        public string $id,
        public string $role,
        public string $name,
        public string $email,
        public ?string $emailVerifiedAt,
        public ?string $avatarUrl,
        public array $accessibilitySettings,
    ) {}

    public static function create(User $user): self
    {
        return new self(
            id: $user->id()->value(),
            role: $user->role()->value(),
            name: $user->name()->value(),
            email: $user->email()->value(),
            emailVerifiedAt: $user->emailVerifiedAt()?->format(\DateTimeInterface::ATOM),
            avatarUrl: $user->avatarUrl()?->value(),
            accessibilitySettings: $user->accessibilitySettings()->toArray(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'user' => [
                'id' => $this->id,
                'role' => $this->role,
                'name' => $this->name,
                'email' => $this->email,
                'email_verified_at' => $this->emailVerifiedAt,
                'avatar_url' => $this->avatarUrl,
                'accessibility_settings' => $this->accessibilitySettings,
            ],
        ];
    }
}
