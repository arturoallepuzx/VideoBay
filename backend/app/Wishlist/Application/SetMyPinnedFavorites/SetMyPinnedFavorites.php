<?php

declare(strict_types=1);

namespace App\Wishlist\Application\SetMyPinnedFavorites;

use App\Shared\Domain\ValueObject\Uuid;
use App\Wishlist\Application\ListMyPinnedFavorites\ListMyPinnedFavoritesResponse;
use App\Wishlist\Domain\Entity\PinnedFavorite;
use App\Wishlist\Domain\Exception\InvalidPinnedSlotsException;
use App\Wishlist\Domain\Interfaces\MovieListItemResolverInterface;
use App\Wishlist\Domain\Interfaces\PinnedFavoriteRepositoryInterface;

class SetMyPinnedFavorites
{
    public function __construct(
        private PinnedFavoriteRepositoryInterface $repository,
        private MovieListItemResolverInterface $movieListItemResolver,
        private int $maxSlots,
    ) {}

    /**
     * @param  list<array{position: int, movie_uuid: string}>  $slots
     */
    public function __invoke(string $userUuid, array $slots): ListMyPinnedFavoritesResponse
    {
        $userId = Uuid::create($userUuid);

        $this->assertSlotsShape($slots);
        $movieIds = $this->parseMovieIds($slots);
        $this->assertMoviesExist($movieIds);

        $pins = array_map(
            fn (array $slot): PinnedFavorite => PinnedFavorite::dddCreate(
                $userId,
                $slot['position'],
                Uuid::create($slot['movie_uuid']),
            ),
            $slots,
        );

        $this->repository->replaceAllForUser($userId, $pins);

        $stored = $this->repository->listByUser($userId);
        $views = $this->movieListItemResolver->resolveMany(
            array_map(fn (PinnedFavorite $pin): Uuid => $pin->movieId(), $stored),
        );

        return ListMyPinnedFavoritesResponse::create($stored, $views);
    }

    /**
     * @param  list<array{position: int, movie_uuid: string}>  $slots
     */
    private function assertSlotsShape(array $slots): void
    {
        if (count($slots) > $this->maxSlots) {
            throw InvalidPinnedSlotsException::tooManySlots(count($slots), $this->maxSlots);
        }

        $seenPositions = [];
        $seenMovies = [];

        foreach ($slots as $slot) {
            $position = $slot['position'];

            if ($position < 1 || $position > $this->maxSlots) {
                throw InvalidPinnedSlotsException::positionOutOfRange($position, $this->maxSlots);
            }

            if (isset($seenPositions[$position])) {
                throw InvalidPinnedSlotsException::duplicatePosition($position);
            }
            $seenPositions[$position] = true;

            $movieUuid = $slot['movie_uuid'];
            if (isset($seenMovies[$movieUuid])) {
                throw InvalidPinnedSlotsException::duplicateMovie($movieUuid);
            }
            $seenMovies[$movieUuid] = true;
        }
    }

    /**
     * @param  list<array{position: int, movie_uuid: string}>  $slots
     * @return list<Uuid>
     */
    private function parseMovieIds(array $slots): array
    {
        return array_map(
            fn (array $slot): Uuid => Uuid::create($slot['movie_uuid']),
            $slots,
        );
    }

    /**
     * @param  list<Uuid>  $movieIds
     */
    private function assertMoviesExist(array $movieIds): void
    {
        if ($movieIds === []) {
            return;
        }

        $views = $this->movieListItemResolver->resolveMany($movieIds);

        foreach ($movieIds as $movieId) {
            if (! isset($views[$movieId->value()])) {
                throw InvalidPinnedSlotsException::movieNotFound($movieId->value());
            }
        }
    }
}
