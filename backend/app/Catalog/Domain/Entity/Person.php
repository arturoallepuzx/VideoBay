<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\ValueObject\ImagePath;
use App\Catalog\Domain\ValueObject\Overview;
use App\Catalog\Domain\ValueObject\PersonName;
use App\Catalog\Domain\ValueObject\TmdbId;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class Person
{
    private bool $modified = false;

    private function __construct(
        private Uuid $id,
        private ?TmdbId $tmdbId,
        private PersonName $name,
        private ?Overview $biography,
        private ?ImagePath $profilePath,
        private ?DomainDateTime $birthday,
        private ?DomainDateTime $deathday,
        private ?string $placeOfBirth,
        private ?DomainDateTime $cachedAt,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        ?TmdbId $tmdbId,
        PersonName $name,
        ?Overview $biography = null,
        ?ImagePath $profilePath = null,
        ?DomainDateTime $birthday = null,
        ?DomainDateTime $deathday = null,
        ?string $placeOfBirth = null,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $tmdbId,
            $name,
            $biography,
            $profilePath,
            $birthday,
            $deathday,
            $placeOfBirth,
            $now,
            $now,
            $now,
        );
    }

    public static function fromPersistence(
        string $id,
        ?int $tmdbId,
        string $name,
        ?string $biography,
        ?string $profilePath,
        ?\DateTimeImmutable $birthday,
        ?\DateTimeImmutable $deathday,
        ?string $placeOfBirth,
        ?\DateTimeImmutable $cachedAt,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            $tmdbId !== null ? TmdbId::create($tmdbId) : null,
            PersonName::create($name),
            $biography !== null ? Overview::create($biography) : null,
            $profilePath !== null ? ImagePath::create($profilePath) : null,
            $birthday !== null ? DomainDateTime::create($birthday) : null,
            $deathday !== null ? DomainDateTime::create($deathday) : null,
            $placeOfBirth,
            $cachedAt !== null ? DomainDateTime::create($cachedAt) : null,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function refreshFromTmdb(
        PersonName $name,
        ?Overview $biography,
        ?ImagePath $profilePath,
        ?DomainDateTime $birthday,
        ?DomainDateTime $deathday,
        ?string $placeOfBirth,
    ): void {
        $this->name = $name;
        $this->biography = $biography;
        $this->profilePath = $profilePath;
        $this->birthday = $birthday;
        $this->deathday = $deathday;
        $this->placeOfBirth = $placeOfBirth;
        $this->cachedAt = DomainDateTime::now();
        $this->touch();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function tmdbId(): ?TmdbId
    {
        return $this->tmdbId;
    }

    public function name(): PersonName
    {
        return $this->name;
    }

    public function biography(): ?Overview
    {
        return $this->biography;
    }

    public function profilePath(): ?ImagePath
    {
        return $this->profilePath;
    }

    public function birthday(): ?DomainDateTime
    {
        return $this->birthday;
    }

    public function deathday(): ?DomainDateTime
    {
        return $this->deathday;
    }

    public function placeOfBirth(): ?string
    {
        return $this->placeOfBirth;
    }

    public function cachedAt(): ?DomainDateTime
    {
        return $this->cachedAt;
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
