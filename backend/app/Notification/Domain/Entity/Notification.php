<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity;

use App\Notification\Domain\ValueObject\NotificationMetadata;
use App\Notification\Domain\ValueObject\NotificationTitle;
use App\Notification\Domain\ValueObject\NotificationType;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class Notification
{
    private bool $modified = false;

    private function __construct(
        private Uuid $id,
        private Uuid $userId,
        private NotificationType $type,
        private NotificationTitle $title,
        private ?string $body,
        private ?string $actionUrl,
        private NotificationMetadata $metadata,
        private ?DomainDateTime $readAt,
        private DomainDateTime $createdAt,
    ) {}

    public static function dddCreate(
        Uuid $userId,
        NotificationType $type,
        NotificationTitle $title,
        ?string $body,
        ?string $actionUrl,
        NotificationMetadata $metadata,
    ): self {
        return new self(
            Uuid::generate(),
            $userId,
            $type,
            $title,
            self::normalizeNullable($body),
            self::normalizeNullable($actionUrl),
            $metadata,
            null,
            DomainDateTime::now(),
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function fromPersistence(
        string $id,
        string $userId,
        string $type,
        string $title,
        ?string $body,
        ?string $actionUrl,
        array $metadata,
        ?\DateTimeImmutable $readAt,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self(
            Uuid::create($id),
            Uuid::create($userId),
            NotificationType::create($type),
            NotificationTitle::create($title),
            $body,
            $actionUrl,
            NotificationMetadata::create($metadata),
            $readAt !== null ? DomainDateTime::create($readAt) : null,
            DomainDateTime::create($createdAt),
        );
    }

    public function markAsRead(DomainDateTime $readAt): void
    {
        if ($this->readAt !== null) {
            return;
        }

        $this->readAt = $readAt;
        $this->modified = true;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function type(): NotificationType
    {
        return $this->type;
    }

    public function title(): NotificationTitle
    {
        return $this->title;
    }

    public function body(): ?string
    {
        return $this->body;
    }

    public function actionUrl(): ?string
    {
        return $this->actionUrl;
    }

    public function metadata(): NotificationMetadata
    {
        return $this->metadata;
    }

    public function readAt(): ?DomainDateTime
    {
        return $this->readAt;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function wasModified(): bool
    {
        return $this->modified;
    }

    private static function normalizeNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }
}
