<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Query;

use App\Catalog\Domain\ValueObject\MovieCardView;
use App\Catalog\Domain\ValueObject\MovieCatalogCriteria;
use App\Catalog\Infrastructure\Persistence\Models\EloquentMovie;
use Illuminate\Database\Eloquent\Builder;

abstract class AbstractEloquentMovieCatalogFinder
{
    public function __construct(protected EloquentMovie $model) {}

    /**
     * @return array{items: list<MovieCardView>, total: int, page: int, totalPages: int}
     */
    public function find(MovieCatalogCriteria $criteria): array
    {
        $base = $this->model->newQuery();

        $this->applyAvailabilityFilter($base);
        $this->applyGenre($base, $criteria);
        $this->applyYear($base, $criteria);

        $total = (int) (clone $base)->count();

        $rows = $this->applySort($base, $criteria)
            ->forPage($criteria->page(), $criteria->perPage())
            ->get(['uuid', 'title', 'poster_path', 'release_date', 'tmdb_rating']);

        $items = $rows->map(fn (EloquentMovie $m): MovieCardView => $this->toCardView($m))->all();

        $totalPages = $total > 0 ? (int) ceil($total / $criteria->perPage()) : 0;

        return ['items' => $items, 'total' => $total, 'page' => $criteria->page(), 'totalPages' => $totalPages];
    }

    /**
     * @param  Builder<EloquentMovie>  $query
     */
    abstract protected function applyAvailabilityFilter(Builder $query): void;

    /**
     * @param  Builder<EloquentMovie>  $query
     */
    private function applyGenre(Builder $query, MovieCatalogCriteria $criteria): void
    {
        if ($criteria->genre() === null) {
            return;
        }

        $query->whereExists(function ($sub) use ($criteria): void {
            $sub->selectRaw('1')
                ->from('movie_genre as mg')
                ->join('genres as g', 'g.id', '=', 'mg.genre_id')
                ->whereColumn('mg.movie_id', 'movies.id')
                ->where('g.name', $criteria->genre());
        });
    }

    /**
     * @param  Builder<EloquentMovie>  $query
     */
    private function applyYear(Builder $query, MovieCatalogCriteria $criteria): void
    {
        if ($criteria->yearFrom() !== null) {
            $query->where('release_date', '>=', sprintf('%04d-01-01', $criteria->yearFrom()));
        }

        if ($criteria->yearTo() !== null) {
            $query->where('release_date', '<=', sprintf('%04d-12-31', $criteria->yearTo()));
        }
    }

    /**
     * @param  Builder<EloquentMovie>  $query
     * @return Builder<EloquentMovie>
     */
    private function applySort(Builder $query, MovieCatalogCriteria $criteria): Builder
    {
        return match ($criteria->sort()) {
            MovieCatalogCriteria::SORT_TITLE => $query->orderBy('title')->orderBy('id'),
            MovieCatalogCriteria::SORT_RATING => $query->orderByDesc('tmdb_rating')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderBy('id'),
        };
    }

    private function toCardView(EloquentMovie $model): MovieCardView
    {
        $releaseYear = $model->release_date !== null
            ? (int) (new \DateTimeImmutable((string) $model->release_date))->format('Y')
            : null;

        return MovieCardView::create(
            (string) $model->uuid,
            (string) $model->title,
            $model->poster_path !== null ? (string) $model->poster_path : null,
            $releaseYear,
            $model->tmdb_rating !== null ? (float) $model->tmdb_rating : null,
        );
    }
}
