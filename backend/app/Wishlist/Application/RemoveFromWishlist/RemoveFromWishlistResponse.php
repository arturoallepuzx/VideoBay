<?php

declare(strict_types=1);

namespace App\Wishlist\Application\RemoveFromWishlist;

use App\Shared\Domain\ValueObject\Uuid;

final readonly class RemoveFromWishlistResponse
{
    private function __construct(
        public string $movieId,
        public bool $wasRemoved,
    ) {}

    public static function create(Uuid $movieId, bool $wasRemoved): self
    {
        return new self($movieId->value(), $wasRemoved);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'movie_id' => $this->movieId,
            'was_removed' => $this->wasRemoved,
        ];
    }
}
