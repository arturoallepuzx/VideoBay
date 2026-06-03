<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

class MovieCardView
{
    private function __construct(
        private string $uuid,
        private string $title,
        private ?string $posterPath,
        private ?int $releaseYear,
        private ?float $tmdbRating,
    ) {}

    public static function create(
        string $uuid,
        string $title,
        ?string $posterPath,
        ?int $releaseYear,
        ?float $tmdbRating,
    ): self {
        return new self($uuid, $title, $posterPath, $releaseYear, $tmdbRating);
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

    public function tmdbRating(): ?float
    {
        return $this->tmdbRating;
    }
}
