<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserRole;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\ValueObject\AccessibilitySettings;
use App\User\Domain\ValueObject\AvatarUrl;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\UserName;

class User
{
    private bool $modified = false;

    private function __construct(
        private Uuid $id,
        private UserRole $role,
        private UserName $name,
        private Email $email,
        private PasswordHash $passwordHash,
        private ?AvatarUrl $avatarUrl,
        private ?DomainDateTime $emailVerifiedAt,
        private AccessibilitySettings $accessibilitySettings,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        UserRole $role,
        UserName $name,
        Email $email,
        PasswordHash $passwordHash,
        ?AvatarUrl $avatarUrl = null,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $role,
            $name,
            $email,
            $passwordHash,
            $avatarUrl,
            null,
            AccessibilitySettings::empty(),
            $now,
            $now,
        );
    }

    /** @param array<string, mixed> $accessibilitySettings */
    public static function fromPersistence(
        string $id,
        string $role,
        string $name,
        string $email,
        string $passwordHash,
        ?string $avatarUrl,
        ?\DateTimeImmutable $emailVerifiedAt,
        array $accessibilitySettings,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            UserRole::create($role),
            UserName::create($name),
            Email::create($email),
            PasswordHash::create($passwordHash),
            $avatarUrl !== null ? AvatarUrl::create($avatarUrl) : null,
            $emailVerifiedAt !== null ? DomainDateTime::create($emailVerifiedAt) : null,
            AccessibilitySettings::create($accessibilitySettings),
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function updateName(UserName $name): void
    {
        if ($this->name->equals($name)) {
            return;
        }

        $this->name = $name;
        $this->touch();
    }

    public function updateEmail(Email $email): void
    {
        if ($this->email->equals($email)) {
            return;
        }

        $this->email = $email;
        $this->emailVerifiedAt = null;
        $this->touch();
    }

    public function updateRole(UserRole $role): void
    {
        if ($this->role->equals($role)) {
            return;
        }

        $this->role = $role;
        $this->touch();
    }

    public function updateAvatarUrl(?AvatarUrl $avatarUrl): void
    {
        if ($this->avatarUrl === null && $avatarUrl === null) {
            return;
        }

        if ($this->avatarUrl !== null && $avatarUrl !== null && $this->avatarUrl->equals($avatarUrl)) {
            return;
        }

        $this->avatarUrl = $avatarUrl;
        $this->touch();
    }

    public function updateAccessibilitySettings(AccessibilitySettings $accessibilitySettings): void
    {
        if ($this->accessibilitySettings->equals($accessibilitySettings)) {
            return;
        }

        $this->accessibilitySettings = $accessibilitySettings;
        $this->touch();
    }

    public function verifyEmail(): void
    {
        if ($this->emailVerifiedAt !== null) {
            return;
        }

        $this->emailVerifiedAt = DomainDateTime::now();
        $this->touch();
    }

    public function anonymize(): void
    {
        $suffix = substr($this->id->value(), 0, 8);

        $this->name = UserName::create('Deleted user '.$suffix);
        $this->email = Email::create('deleted-'.$suffix.'@deleted.invalid');
        $this->avatarUrl = null;
        $this->emailVerifiedAt = null;
        $this->accessibilitySettings = AccessibilitySettings::empty();
        $this->touch();
    }

    public function changePassword(PasswordHash $passwordHash): void
    {
        if ($this->passwordHash->equals($passwordHash)) {
            return;
        }

        $this->passwordHash = $passwordHash;
        $this->touch();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function role(): UserRole
    {
        return $this->role;
    }

    public function name(): UserName
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function passwordHash(): PasswordHash
    {
        return $this->passwordHash;
    }

    public function avatarUrl(): ?AvatarUrl
    {
        return $this->avatarUrl;
    }

    public function emailVerifiedAt(): ?DomainDateTime
    {
        return $this->emailVerifiedAt;
    }

    public function accessibilitySettings(): AccessibilitySettings
    {
        return $this->accessibilitySettings;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function wasModified(): bool
    {
        return $this->modified;
    }

    private function touch(): void
    {
        $this->modified = true;
        $this->updatedAt = DomainDateTime::now();
    }
}
