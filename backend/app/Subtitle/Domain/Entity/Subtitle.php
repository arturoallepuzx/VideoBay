<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\ValueObject\SubtitleFormat;
use App\Subtitle\Domain\ValueObject\SubtitleLabel;
use App\Subtitle\Domain\ValueObject\SubtitleLanguage;
use App\Subtitle\Domain\ValueObject\SubtitleSource;

class Subtitle
{
    private bool $modified = false;

    private function __construct(
        private Uuid $id,
        private Uuid $movieId,
        private SubtitleLanguage $language,
        private SubtitleLabel $label,
        private SubtitleSource $source,
        private ?string $provider,
        private ?string $externalId,
        private string $filePath,
        private SubtitleFormat $originalFormat,
        private ?Uuid $uploadedByUserId,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $deletedAt,
    ) {}

    public static function dddCreate(
        Uuid $id,
        Uuid $movieId,
        SubtitleLanguage $language,
        SubtitleLabel $label,
        SubtitleSource $source,
        ?string $provider,
        ?string $externalId,
        string $filePath,
        SubtitleFormat $originalFormat,
        ?Uuid $uploadedByUserId,
    ): self {
        $normalizedFilePath = trim($filePath);

        if ($normalizedFilePath === '') {
            throw new \InvalidArgumentException('Subtitle file path cannot be empty.');
        }

        if ($source->isExternal() && (trim((string) $provider) === '' || trim((string) $externalId) === '')) {
            throw new \InvalidArgumentException('External subtitles require provider and external id.');
        }

        $now = DomainDateTime::now();

        return new self(
            $id,
            $movieId,
            $language,
            $label,
            $source,
            $provider !== null ? strtolower(trim($provider)) : null,
            $externalId !== null ? trim($externalId) : null,
            $normalizedFilePath,
            $originalFormat,
            $uploadedByUserId,
            $now,
            $now,
            null,
        );
    }

    public static function fromPersistence(
        string $id,
        string $movieId,
        string $language,
        string $label,
        string $source,
        ?string $provider,
        ?string $externalId,
        string $filePath,
        string $originalFormat,
        ?string $uploadedByUserId,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $deletedAt,
    ): self {
        return new self(
            Uuid::create($id),
            Uuid::create($movieId),
            SubtitleLanguage::create($language),
            SubtitleLabel::create($label),
            SubtitleSource::create($source),
            $provider,
            $externalId,
            $filePath,
            SubtitleFormat::create($originalFormat),
            $uploadedByUserId !== null ? Uuid::create($uploadedByUserId) : null,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
            $deletedAt !== null ? DomainDateTime::create($deletedAt) : null,
        );
    }

    public function softDelete(): void
    {
        if ($this->deletedAt !== null) {
            return;
        }

        $this->deletedAt = DomainDateTime::now();
        $this->touch();
    }

    public function wasModified(): bool
    {
        return $this->modified;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function movieId(): Uuid
    {
        return $this->movieId;
    }

    public function language(): SubtitleLanguage
    {
        return $this->language;
    }

    public function label(): SubtitleLabel
    {
        return $this->label;
    }

    public function source(): SubtitleSource
    {
        return $this->source;
    }

    public function provider(): ?string
    {
        return $this->provider;
    }

    public function externalId(): ?string
    {
        return $this->externalId;
    }

    public function filePath(): string
    {
        return $this->filePath;
    }

    public function originalFormat(): SubtitleFormat
    {
        return $this->originalFormat;
    }

    public function uploadedByUserId(): ?Uuid
    {
        return $this->uploadedByUserId;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DomainDateTime
    {
        return $this->deletedAt;
    }

    private function touch(): void
    {
        $this->modified = true;
        $this->updatedAt = DomainDateTime::now();
    }
}
