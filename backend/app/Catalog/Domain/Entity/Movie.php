<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\ValueObject\ImagePath;
use App\Catalog\Domain\ValueObject\ImdbId;
use App\Catalog\Domain\ValueObject\LanguageCode;
use App\Catalog\Domain\ValueObject\MovieTitle;
use App\Catalog\Domain\ValueObject\Overview;
use App\Catalog\Domain\ValueObject\RuntimeMinutes;
use App\Catalog\Domain\ValueObject\TmdbId;
use App\Catalog\Domain\ValueObject\TmdbRating;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class Movie
{
    private bool $modified = false;

    private function __construct(
        private Uuid $id,
        private ?TmdbId $tmdbId,
        private ?ImdbId $imdbId,
        private MovieTitle $title,
        private ?MovieTitle $originalTitle,
        private ?Overview $overview,
        private ?DomainDateTime $releaseDate,
        private ?RuntimeMinutes $runtimeMinutes,
        private ?LanguageCode $originalLanguage,
        private ?ImagePath $posterPath,
        private ?ImagePath $backdropPath,
        private ?TmdbRating $tmdbRating,
        private ?DomainDateTime $cachedAt,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        ?TmdbId $tmdbId,
        ?ImdbId $imdbId,
        MovieTitle $title,
        ?MovieTitle $originalTitle = null,
        ?Overview $overview = null,
        ?DomainDateTime $releaseDate = null,
        ?RuntimeMinutes $runtimeMinutes = null,
        ?LanguageCode $originalLanguage = null,
        ?ImagePath $posterPath = null,
        ?ImagePath $backdropPath = null,
        ?TmdbRating $tmdbRating = null,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $tmdbId,
            $imdbId,
            $title,
            $originalTitle,
            $overview,
            $releaseDate,
            $runtimeMinutes,
            $originalLanguage,
            $posterPath,
            $backdropPath,
            $tmdbRating,
            $now,
            $now,
            $now,
        );
    }

    public static function fromPersistence(
        string $id,
        ?int $tmdbId,
        ?string $imdbId,
        string $title,
        ?string $originalTitle,
        ?string $overview,
        ?\DateTimeImmutable $releaseDate,
        ?int $runtimeMinutes,
        ?string $originalLanguage,
        ?string $posterPath,
        ?string $backdropPath,
        ?float $tmdbRating,
        ?\DateTimeImmutable $cachedAt,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            $tmdbId !== null ? TmdbId::create($tmdbId) : null,
            $imdbId !== null ? ImdbId::create($imdbId) : null,
            MovieTitle::create($title),
            $originalTitle !== null ? MovieTitle::create($originalTitle) : null,
            $overview !== null ? Overview::create($overview) : null,
            $releaseDate !== null ? DomainDateTime::create($releaseDate) : null,
            $runtimeMinutes !== null ? RuntimeMinutes::create($runtimeMinutes) : null,
            $originalLanguage !== null ? LanguageCode::create($originalLanguage) : null,
            $posterPath !== null ? ImagePath::create($posterPath) : null,
            $backdropPath !== null ? ImagePath::create($backdropPath) : null,
            $tmdbRating !== null ? TmdbRating::create($tmdbRating) : null,
            $cachedAt !== null ? DomainDateTime::create($cachedAt) : null,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function refreshFromTmdb(
        ?ImdbId $imdbId,
        MovieTitle $title,
        ?MovieTitle $originalTitle,
        ?Overview $overview,
        ?DomainDateTime $releaseDate,
        ?RuntimeMinutes $runtimeMinutes,
        ?LanguageCode $originalLanguage,
        ?ImagePath $posterPath,
        ?ImagePath $backdropPath,
        ?TmdbRating $tmdbRating,
    ): void {
        $this->imdbId = $imdbId;
        $this->title = $title;
        $this->originalTitle = $originalTitle;
        $this->overview = $overview;
        $this->releaseDate = $releaseDate;
        $this->runtimeMinutes = $runtimeMinutes;
        $this->originalLanguage = $originalLanguage;
        $this->posterPath = $posterPath;
        $this->backdropPath = $backdropPath;
        $this->tmdbRating = $tmdbRating;
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

    public function imdbId(): ?ImdbId
    {
        return $this->imdbId;
    }

    public function title(): MovieTitle
    {
        return $this->title;
    }

    public function originalTitle(): ?MovieTitle
    {
        return $this->originalTitle;
    }

    public function overview(): ?Overview
    {
        return $this->overview;
    }

    public function releaseDate(): ?DomainDateTime
    {
        return $this->releaseDate;
    }

    public function runtimeMinutes(): ?RuntimeMinutes
    {
        return $this->runtimeMinutes;
    }

    public function originalLanguage(): ?LanguageCode
    {
        return $this->originalLanguage;
    }

    public function posterPath(): ?ImagePath
    {
        return $this->posterPath;
    }

    public function backdropPath(): ?ImagePath
    {
        return $this->backdropPath;
    }

    public function tmdbRating(): ?TmdbRating
    {
        return $this->tmdbRating;
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
