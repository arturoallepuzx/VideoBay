<?php

declare(strict_types=1);

namespace App\Wishlist\Application\AddToWishlist;

use App\Shared\Domain\ValueObject\Uuid;

final readonly class AddToWishlistResponse
{
    private function __construct(
        public string $movieId,
        public bool $wasNew,
    ) {}

    public static function create(Uuid $movieId, bool $wasNew): self
    {
        return new self($movieId->value(), $wasNew);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'movie_id' => $this->movieId,
            'was_new' => $this->wasNew,
        ];
    }
}
