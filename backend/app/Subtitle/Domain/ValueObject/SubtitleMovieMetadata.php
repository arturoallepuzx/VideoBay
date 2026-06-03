<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;

class SubtitleMovieMetadata
{
    private function __construct(
        private Uuid $movieId,
        private string $title,
        private ?int $tmdbId,
        private ?string $imdbId,
        private ?int $releaseYear,
    ) {}

    public static function create(Uuid $movieId, string $title, ?int $tmdbId, ?string $imdbId, ?int $releaseYear): self
    {
        $normalizedTitle = trim($title);

        if ($normalizedTitle === '') {
            throw new \InvalidArgumentException('Subtitle movie metadata title cannot be empty.');
        }

        return new self($movieId, $normalizedTitle, $tmdbId, $imdbId !== null ? trim($imdbId) : null, $releaseYear);
    }

    public function movieId(): Uuid
    {
        return $this->movieId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function tmdbId(): ?int
    {
        return $this->tmdbId;
    }

    public function imdbId(): ?string
    {
        return $this->imdbId;
    }

    public function imdbIdWithoutPrefix(): ?string
    {
        if ($this->imdbId === null) {
            return null;
        }

        return ltrim($this->imdbId, 't');
    }

    public function releaseYear(): ?int
    {
        return $this->releaseYear;
    }
}
