<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Persistence\Repositories;

use App\Inventory\Domain\Entity\PhysicalCopy;
use App\Inventory\Domain\Exception\PhysicalCopyAlreadyExistsException;
use App\Inventory\Domain\Interfaces\PhysicalCopyRepositoryInterface;
use App\Inventory\Domain\ValueObject\SkuCode;
use App\Inventory\Infrastructure\Persistence\Models\EloquentPhysicalCopy;
use App\Shared\Domain\Interfaces\SystemCurrencyProviderInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\MovieIdResolverInterface;
use App\Shared\Infrastructure\Persistence\MysqlUniqueConstraintViolationDetector;
use Illuminate\Database\QueryException;

class EloquentPhysicalCopyRepository implements PhysicalCopyRepositoryInterface
{
    private const UNIQUE_SKU_CONSTRAINT = 'physical_copies_sku_active_unique';

    private string $currency;

    public function __construct(
        private EloquentPhysicalCopy $model,
        private MysqlUniqueConstraintViolationDetector $uniqueConstraintViolationDetector,
        private MovieIdResolverInterface $movieIdResolver,
        SystemCurrencyProviderInterface $currencyProvider,
    ) {
        $this->currency = $currencyProvider->getCurrency();
    }

    public function create(PhysicalCopy $copy): void
    {
        $movieInternalId = $this->movieIdResolver->toInternalId($copy->movieId());

        try {
            $this->model->newQuery()->create([
                'uuid' => $copy->id()->value(),
                'movie_id' => $movieInternalId,
                'sku' => $copy->sku()->value(),
                'barcode' => $copy->barcode()?->value(),
                'format' => $copy->format()->value(),
                'region' => $copy->region()?->value(),
                'condition' => $copy->condition()->value(),
                'cover_photo_url' => $copy->coverPhotoUrl(),
                'price_cents' => $copy->price()->cents(),
                'stock_available' => $copy->stockAvailable()->value(),
                'stock_reserved' => $copy->stockReserved()->value(),
                'active' => $copy->isActive(),
                'created_at' => $copy->createdAt()->value(),
                'updated_at' => $copy->updatedAt()->value(),
            ]);
        } catch (QueryException $e) {
            if ($this->uniqueConstraintViolationDetector->matches($e, self::UNIQUE_SKU_CONSTRAINT)) {
                throw PhysicalCopyAlreadyExistsException::forSku($copy->sku());
            }

            throw $e;
        }
    }

    public function update(PhysicalCopy $copy): void
    {
        $this->model->newQuery()
            ->where('uuid', $copy->id()->value())
            ->update([
                'barcode' => $copy->barcode()?->value(),
                'condition' => $copy->condition()->value(),
                'cover_photo_url' => $copy->coverPhotoUrl(),
                'price_cents' => $copy->price()->cents(),
                'stock_available' => $copy->stockAvailable()->value(),
                'stock_reserved' => $copy->stockReserved()->value(),
                'active' => $copy->isActive(),
                'updated_at' => $copy->updatedAt()->value(),
            ]);
    }

    public function findByUuid(Uuid $uuid): ?PhysicalCopy
    {
        $row = $this->model->newQuery()
            ->leftJoin('movies', 'physical_copies.movie_id', '=', 'movies.id')
            ->where('physical_copies.uuid', $uuid->value())
            ->select(['physical_copies.*', 'movies.uuid as movie_uuid'])
            ->first();

        return $row !== null ? $this->toDomainEntity($row, (string) $row->getAttribute('movie_uuid')) : null;
    }

    public function findByUuidForUpdate(Uuid $uuid): ?PhysicalCopy
    {
        $model = $this->model->newQuery()
            ->where('uuid', $uuid->value())
            ->lockForUpdate()
            ->first();

        if ($model === null) {
            return null;
        }

        $movieUuid = $this->movieIdResolver->toDomainUuid((int) $model->getAttribute('movie_id'));

        return $this->toDomainEntity($model, $movieUuid->value());
    }

    public function findManyByUuids(array $uuids): array
    {
        if ($uuids === []) {
            return [];
        }

        $values = array_map(fn (Uuid $u): string => $u->value(), $uuids);

        $rows = $this->model->newQuery()
            ->leftJoin('movies', 'physical_copies.movie_id', '=', 'movies.id')
            ->whereIn('physical_copies.uuid', $values)
            ->select(['physical_copies.*', 'movies.uuid as movie_uuid'])
            ->get();

        $byUuid = [];
        foreach ($rows as $row) {
            $entity = $this->toDomainEntity($row, (string) $row->getAttribute('movie_uuid'));
            $byUuid[$entity->id()->value()] = $entity;
        }

        return $byUuid;
    }

    public function existsBySku(SkuCode $sku): bool
    {
        return $this->model->newQuery()
            ->where('sku', $sku->value())
            ->exists();
    }

    public function listAvailable(?Uuid $movieId, int $page, int $perPage): array
    {
        $base = $this->model->newQuery()
            ->leftJoin('movies', 'physical_copies.movie_id', '=', 'movies.id')
            ->where('physical_copies.active', true)
            ->where('physical_copies.stock_available', '>', 0);

        if ($movieId !== null) {
            $base->where('movies.uuid', $movieId->value());
        }

        $total = (clone $base)->count('physical_copies.id');

        $rows = $base
            ->select(['physical_copies.*', 'movies.uuid as movie_uuid'])
            ->orderBy('physical_copies.created_at', 'desc')
            ->forPage($page, $perPage)
            ->get();

        return [
            'copies' => $rows
                ->map(fn (EloquentPhysicalCopy $m): PhysicalCopy => $this->toDomainEntity(
                    $m,
                    (string) $m->getAttribute('movie_uuid'),
                ))
                ->all(),
            'total' => $total,
            'page' => $page,
            'totalPages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    private function toDomainEntity(EloquentPhysicalCopy $model, string $movieUuid): PhysicalCopy
    {
        return PhysicalCopy::fromPersistence(
            (string) $model->uuid,
            $movieUuid,
            (string) $model->sku,
            $model->barcode !== null ? (string) $model->barcode : null,
            (string) $model->format,
            $model->region !== null ? (string) $model->region : null,
            (string) $model->condition,
            $model->cover_photo_url !== null ? (string) $model->cover_photo_url : null,
            (int) $model->price_cents,
            $this->currency,
            (int) $model->stock_available,
            (int) $model->stock_reserved,
            (bool) $model->active,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
