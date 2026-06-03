<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Persistence\MovieIdResolverInterface;
use App\Shared\Infrastructure\Persistence\UserIdResolverInterface;
use App\Wishlist\Domain\Entity\WishlistItem;
use App\Wishlist\Domain\Interfaces\WishlistItemRepositoryInterface;
use Illuminate\Database\ConnectionInterface;

class EloquentWishlistItemRepository implements WishlistItemRepositoryInterface
{
    private const TABLE = 'wishlist_items';

    public function __construct(
        private ConnectionInterface $connection,
        private UserIdResolverInterface $userIdResolver,
        private MovieIdResolverInterface $movieIdResolver,
    ) {}

    public function exists(Uuid $userId, Uuid $movieId): bool
    {
        return $this->connection->table(self::TABLE)
            ->where('user_id', $this->userIdResolver->toInternalId($userId))
            ->where('movie_id', $this->movieIdResolver->toInternalId($movieId))
            ->exists();
    }

    public function add(WishlistItem $item): bool
    {
        $affected = $this->connection->table(self::TABLE)->insertOrIgnore([
            'user_id' => $this->userIdResolver->toInternalId($item->userId()),
            'movie_id' => $this->movieIdResolver->toInternalId($item->movieId()),
            'created_at' => $item->addedAt()->value(),
        ]);

        return $affected > 0;
    }

    public function remove(Uuid $userId, Uuid $movieId): bool
    {
        $deleted = $this->connection->table(self::TABLE)
            ->where('user_id', $this->userIdResolver->toInternalId($userId))
            ->where('movie_id', $this->movieIdResolver->toInternalId($movieId))
            ->delete();

        return $deleted > 0;
    }

    public function listByUser(Uuid $userId, int $page, int $perPage): array
    {
        $internalUserId = $this->userIdResolver->toInternalId($userId);

        $base = $this->connection->table(self::TABLE.' as w')
            ->join('movies as m', 'w.movie_id', '=', 'm.id')
            ->where('w.user_id', $internalUserId)
            ->whereNull('m.deleted_at');

        $total = (int) (clone $base)->count();

        $rows = $base
            ->orderByDesc('w.created_at')
            ->forPage($page, $perPage)
            ->get(['w.created_at', 'm.uuid as movie_uuid']);

        $items = [];
        foreach ($rows as $row) {
            $items[] = WishlistItem::fromPersistence(
                $userId->value(),
                (string) $row->movie_uuid,
                new \DateTimeImmutable((string) $row->created_at),
            );
        }

        $totalPages = (int) max(1, ceil($total / max(1, $perPage)));

        return ['items' => $items, 'total' => $total, 'page' => $page, 'totalPages' => $totalPages];
    }

    public function listUserIdsByMovie(Uuid $movieId): array
    {
        $rows = $this->connection->table(self::TABLE.' as w')
            ->join('users as u', 'w.user_id', '=', 'u.id')
            ->where('w.movie_id', $this->movieIdResolver->toInternalId($movieId))
            ->whereNull('u.deleted_at')
            ->orderBy('w.created_at')
            ->get(['u.uuid as user_uuid']);

        $userIds = [];
        foreach ($rows as $row) {
            $userIds[] = Uuid::create((string) $row->user_uuid);
        }

        return $userIds;
    }
}
