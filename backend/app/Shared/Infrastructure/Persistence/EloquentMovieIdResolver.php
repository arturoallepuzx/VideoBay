<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Domain\ValueObject\Uuid;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentMovieIdResolver implements MovieIdResolverInterface
{
    private const TABLE = 'movies';

    /** @var array<string, int> */
    private array $uuidToId = [];

    /** @var array<int, string> */
    private array $idToUuid = [];

    public function __construct(private ConnectionInterface $connection) {}

    public function toInternalId(Uuid $movieUuid): int
    {
        $uuid = $movieUuid->value();

        if (isset($this->uuidToId[$uuid])) {
            return $this->uuidToId[$uuid];
        }

        $id = $this->connection->table(self::TABLE)
            ->where('uuid', $uuid)
            ->value('id');

        if ($id === null) {
            throw (new ModelNotFoundException)->setModel(self::TABLE, [$uuid]);
        }

        $internalId = (int) $id;
        $this->uuidToId[$uuid] = $internalId;
        $this->idToUuid[$internalId] = $uuid;

        return $internalId;
    }

    public function toDomainUuid(int $internalId): Uuid
    {
        if (isset($this->idToUuid[$internalId])) {
            return Uuid::create($this->idToUuid[$internalId]);
        }

        $uuid = $this->connection->table(self::TABLE)
            ->where('id', $internalId)
            ->value('uuid');

        if ($uuid === null) {
            throw (new ModelNotFoundException)->setModel(self::TABLE, [$internalId]);
        }

        $uuidValue = (string) $uuid;
        $this->idToUuid[$internalId] = $uuidValue;
        $this->uuidToId[$uuidValue] = $internalId;

        return Uuid::create($uuidValue);
    }
}
