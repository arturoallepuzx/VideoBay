<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Interfaces;

use App\Catalog\Domain\ValueObject\MovieCardView;
use App\Catalog\Domain\ValueObject\MovieCatalogCriteria;
use App\Shared\Domain\ValueObject\Uuid;

interface StreamableMovieFinderInterface
{
    /**
     * @return array{items: list<MovieCardView>, total: int, page: int, totalPages: int}
     */
    public function find(MovieCatalogCriteria $criteria): array;

    public function findReadyVideoUuid(Uuid $movieId): ?string;
}
