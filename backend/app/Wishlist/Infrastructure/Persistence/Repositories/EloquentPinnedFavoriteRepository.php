<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\MovieIdResolverInterface;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;
use App\Wishlist\Domain\Entity\PinnedFavorite;
use App\Wishlist\Domain\Interfaces\PinnedFavoriteRepositoryInterface;
use Illuminate\Database\ConnectionInterface;

class EloquentPinnedFavoriteRepository implements PinnedFavoriteRepositoryInterface
{
    private const TABLE = 'pinned_favorites';

    public function __construct(
        private ConnectionInterface $connection,
        private UserIdResolverInterface $userIdResolver,
        private MovieIdResolverInterface $movieIdResolver,
    ) {}

    public function listByUser(Uuid $userId): array
    {
        $internalUserId = $this->userIdResolver->toInternalId($userId);

        $rows = $this->connection->table(self::TABLE.' as p')
            ->join('movies as m', 'p.movie_id', '=', 'm.id')
            ->where('p.user_id', $internalUserId)
            ->whereNull('m.deleted_at')
            ->orderBy('p.position')
            ->get(['p.position', 'm.uuid as movie_uuid']);

        $pins = [];
        foreach ($rows as $row) {
            $pins[] = PinnedFavorite::fromPersistence(
                $userId->value(),
                (int) $row->position,
                (string) $row->movie_uuid,
            );
        }

        return $pins;
    }

    public function replaceAllForUser(Uuid $userId, array $pins): void
    {
        $internalUserId = $this->userIdResolver->toInternalId($userId);

        $rows = array_map(
            fn (PinnedFavorite $pin): array => [
                'user_id' => $internalUserId,
                'position' => $pin->position(),
                'movie_id' => $this->movieIdResolver->toInternalId($pin->movieId()),
            ],
            $pins,
        );

        $this->connection->transaction(function () use ($internalUserId, $rows): void {
            $this->connection->table(self::TABLE)
                ->where('user_id', $internalUserId)
                ->delete();

            if ($rows !== []) {
                $this->connection->table(self::TABLE)->insert($rows);
            }
        });
    }
}
