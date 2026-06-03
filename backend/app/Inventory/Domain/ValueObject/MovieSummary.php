<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;

class MovieSummary
{
    private function __construct(
        private Uuid $uuid,
        private string $title,
        private ?string $posterPath,
    ) {}

    public static function create(Uuid $uuid, string $title, ?string $posterPath): self
    {
        $normalizedTitle = trim($title);

        if ($normalizedTitle === '') {
            throw new \InvalidArgumentException('Movie summary title cannot be empty.');
        }

        return new self($uuid, $normalizedTitle, $posterPath);
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

    public function equals(self $other): bool
    {
        return $this->uuid->equals($other->uuid)
            && $this->title === $other->title
            && $this->posterPath === $other->posterPath;
    }
}
