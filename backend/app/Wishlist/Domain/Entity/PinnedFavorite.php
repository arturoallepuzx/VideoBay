<?php

declare(strict_types=1);

namespace App\Wishlist\Domain\Entity;

use App\Shared\Domain\ValueObject\Uuid;

class PinnedFavorite
{
    private function __construct(
        private Uuid $userId,
        private int $position,
        private Uuid $movieId,
    ) {}

    public static function dddCreate(Uuid $userId, int $position, Uuid $movieId): self
    {
        return new self($userId, $position, $movieId);
    }

    public static function fromPersistence(
        string $userId,
        int $position,
        string $movieId,
    ): self {
        return new self(
            Uuid::create($userId),
            $position,
            Uuid::create($movieId),
        );
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function movieId(): Uuid
    {
        return $this->movieId;
    }
}
