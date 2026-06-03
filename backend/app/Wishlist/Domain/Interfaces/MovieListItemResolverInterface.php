<?php

declare(strict_types=1);

namespace App\Wishlist\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\Wishlist\Domain\ValueObject\MovieListItemView;

interface MovieListItemResolverInterface
{
    /**
     * @param  list<Uuid>  $movieIds
     * @return array<string, MovieListItemView>
     */
    public function resolveMany(array $movieIds): array;
}
