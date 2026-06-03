<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;

class SubtitleMovieSummary
{
    private function __construct(
        private Uuid $uuid,
        private string $title,
        private ?string $posterPath,
        private ?int $releaseYear,
    ) {}

    public static function create(Uuid $uuid, string $title, ?string $posterPath, ?int $releaseYear): self
    {
        $normalizedTitle = trim($title);

        if ($normalizedTitle === '') {
            throw new \InvalidArgumentException('Subtitle movie summary title cannot be empty.');
        }

        return new self($uuid, $normalizedTitle, $posterPath, $releaseYear);
    }

    public function uuid(): string
    {
        return $this->uuid->value();
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

    public function equals(self $other): bool
    {
        return $this->uuid->equals($other->uuid)
            && $this->title === $other->title
            && $this->posterPath === $other->posterPath
            && $this->releaseYear === $other->releaseYear;
    }
}
