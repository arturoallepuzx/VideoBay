<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Query;

use App\Catalog\Domain\Interfaces\StreamableMovieFinderInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Illuminate\Database\Eloquent\Builder;

class EloquentStreamableMovieFinder extends AbstractEloquentMovieCatalogFinder implements StreamableMovieFinderInterface
{
    private const STATUS_READY = 'ready';

    protected function applyAvailabilityFilter(Builder $query): void
    {
        $query->whereExists(function ($sub): void {
            $sub->selectRaw('1')
                ->from('video_files as vf')
                ->whereColumn('vf.movie_id', 'movies.id')
                ->where('vf.processing_status', self::STATUS_READY)
                ->whereNull('vf.deleted_at');
        });
    }

    public function findReadyVideoUuid(Uuid $movieId): ?string
    {
        $uuid = $this->model->newQuery()
            ->join('video_files as vf', 'vf.movie_id', '=', 'movies.id')
            ->where('movies.uuid', $movieId->value())
            ->where('vf.processing_status', self::STATUS_READY)
            ->whereNull('vf.deleted_at')
            ->value('vf.uuid');

        return $uuid !== null ? (string) $uuid : null;
    }
}
