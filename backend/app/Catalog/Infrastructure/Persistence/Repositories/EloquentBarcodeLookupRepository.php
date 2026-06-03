<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Repositories;

use App\Catalog\Domain\Entity\BarcodeLookup;
use App\Catalog\Domain\Interfaces\BarcodeLookupRepositoryInterface;
use App\Catalog\Infrastructure\Persistence\Models\EloquentBarcodeLookup;
use App\Catalog\Infrastructure\Persistence\Models\EloquentMovie;
use App\Shared\Domain\ValueObject\BarcodeValue;

class EloquentBarcodeLookupRepository implements BarcodeLookupRepositoryInterface
{
    public function __construct(
        private EloquentBarcodeLookup $model,
    ) {}

    public function findByBarcode(BarcodeValue $barcode): ?BarcodeLookup
    {
        $model = $this->model->newQuery()
            ->leftJoin('movies', 'barcode_lookups.movie_id', '=', 'movies.id')
            ->where('barcode_lookups.barcode', $barcode->value())
            ->select([
                'barcode_lookups.*',
                'movies.uuid as movie_uuid',
            ])
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function upsert(BarcodeLookup $lookup): void
    {
        $movieInternalId = $lookup->movieId() !== null
            ? EloquentMovie::query()->where('uuid', $lookup->movieId()->value())->value('id')
            : null;

        $this->model->newQuery()->updateOrCreate(
            ['barcode' => $lookup->barcode()->value()],
            [
                'movie_id' => $movieInternalId,
                'resolved_via' => $lookup->resolvedVia()->value(),
                'confirmed' => $lookup->confirmed(),
                'updated_at' => $lookup->updatedAt()->value(),
            ],
        );
    }

    private function toDomainEntity(EloquentBarcodeLookup $model): BarcodeLookup
    {
        $movieUuid = $model->getAttribute('movie_uuid');

        return BarcodeLookup::fromPersistence(
            (string) $model->barcode,
            $movieUuid !== null ? (string) $movieUuid : null,
            (string) $model->resolved_via,
            (bool) $model->confirmed,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
