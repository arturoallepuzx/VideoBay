<?php

declare(strict_types=1);

namespace App\Wishlist\Domain\ValueObject;

class MovieListItemView
{
    private function __construct(
        private string $uuid,
        private string $title,
        private ?string $posterPath,
        private ?int $releaseYear,
        private ?int $tmdbId,
    ) {}

    public static function create(
        string $uuid,
        string $title,
        ?string $posterPath,
        ?int $releaseYear,
        ?int $tmdbId = null,
    ): self {
        return new self($uuid, $title, $posterPath, $releaseYear, $tmdbId);
    }

    public function tmdbId(): ?int
    {
        return $this->tmdbId;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function posterPath(): ?string
    {
        return $this->posterPath;
    }

    public function releaseYear(): ?int
    {
        return $this->releaseYear;
    }
}
