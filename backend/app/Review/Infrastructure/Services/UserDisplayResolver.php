<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Services;

use App\Review\Domain\Interfaces\UserDisplayResolverInterface;
use App\Review\Domain\ValueObject\UserDisplay;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

class UserDisplayResolver implements UserDisplayResolverInterface
{
    public function resolveMany(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $uuids = array_map(fn (Uuid $u): string => $u->value(), $userIds);

        $rows = EloquentUser::query()
            ->whereIn('uuid', $uuids)
            ->get(['uuid', 'name', 'avatar_url']);

        $displays = [];
        foreach ($rows as $row) {
            $uuid = (string) $row->uuid;
            $displays[$uuid] = UserDisplay::create(
                $uuid,
                (string) $row->name,
                $row->avatar_url !== null ? (string) $row->avatar_url : null,
            );
        }

        return $displays;
    }
}
