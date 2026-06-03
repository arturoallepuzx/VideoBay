<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentUserIdResolver implements UserIdResolverInterface
{
    /** @var array<string, int> */
    private array $uuidToId = [];

    /** @var array<int, string> */
    private array $idToUuid = [];

    public function toInternalId(Uuid $userUuid): int
    {
        $uuid = $userUuid->value();

        if (isset($this->uuidToId[$uuid])) {
            return $this->uuidToId[$uuid];
        }

        $id = EloquentUser::query()
            ->where('uuid', $uuid)
            ->value('id');

        if ($id === null) {
            throw (new ModelNotFoundException)->setModel(EloquentUser::class, [$uuid]);
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

        $uuid = EloquentUser::query()
            ->where('id', $internalId)
            ->value('uuid');

        if ($uuid === null) {
            throw (new ModelNotFoundException)->setModel(EloquentUser::class, [$internalId]);
        }

        $uuidValue = (string) $uuid;
        $this->idToUuid[$internalId] = $uuidValue;
        $this->uuidToId[$uuidValue] = $internalId;

        return Uuid::create($uuidValue);
    }
}
