<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Query;

use App\Catalog\Domain\Interfaces\PurchasableMovieFinderInterface;
use Illuminate\Database\Eloquent\Builder;

class EloquentPurchasableMovieFinder extends AbstractEloquentMovieCatalogFinder implements PurchasableMovieFinderInterface
{
    protected function applyAvailabilityFilter(Builder $query): void
    {
        $query->whereExists(function ($sub): void {
            $sub->selectRaw('1')
                ->from('physical_copies as pc')
                ->whereColumn('pc.movie_id', 'movies.id')
                ->where('pc.active', true)
                ->whereNull('pc.deleted_at');
        });
    }
}
