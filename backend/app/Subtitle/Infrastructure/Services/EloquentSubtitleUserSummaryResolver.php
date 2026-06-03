<?php

declare(strict_types=1);

namespace App\Subtitle\Infrastructure\Services;

use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\Interfaces\SubtitleUserSummaryResolverInterface;
use App\Subtitle\Domain\ValueObject\SubtitleUserSummary;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

class EloquentSubtitleUserSummaryResolver implements SubtitleUserSummaryResolverInterface
{
    public function resolveMany(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $uuids = array_map(fn (Uuid $uuid): string => $uuid->value(), $userIds);

        $rows = EloquentUser::query()
            ->whereIn('uuid', $uuids)
            ->get(['uuid', 'name', 'avatar_url']);

        $summaries = [];
        foreach ($rows as $row) {
            $uuid = (string) $row->uuid;
            $summaries[$uuid] = SubtitleUserSummary::create(
                $uuid,
                (string) $row->name,
                $row->avatar_url !== null ? (string) $row->avatar_url : null,
            );
        }

        return $summaries;
    }
}
