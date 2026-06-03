<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Services;

use App\Catalog\Domain\Interfaces\MovieRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

class NotificationMetadataFactory
{
    public function __construct(private MovieRepositoryInterface $movieRepository) {}

    /** @return array<string, mixed> */
    public function user(Uuid $userId): array
    {
        $row = EloquentUser::query()
            ->where('uuid', $userId->value())
            ->first(['uuid', 'name', 'avatar_url']);

        if ($row === null) {
            return ['uuid' => $userId->value(), 'name' => null, 'avatar_url' => null];
        }

        return [
            'uuid' => (string) $row->uuid,
            'name' => (string) $row->name,
            'avatar_url' => $row->avatar_url !== null ? (string) $row->avatar_url : null,
        ];
    }

    /** @return array<string, mixed> */
    public function movie(Uuid $movieId): array
    {
        $movie = $this->movieRepository->findByUuid($movieId);

        if ($movie === null) {
            return [
                'uuid' => $movieId->value(),
                'title' => null,
                'poster_path' => null,
                'release_year' => null,
            ];
        }

        $releaseDate = $movie->releaseDate()?->value();

        return [
            'uuid' => $movie->id()->value(),
            'title' => $movie->title()->value(),
            'poster_path' => $movie->posterPath()?->value(),
            'release_year' => $releaseDate !== null ? (int) $releaseDate->format('Y') : null,
        ];
    }

    /** @return array<string, mixed> */
    public function review(Uuid $reviewId, ?string $body): array
    {
        return [
            'uuid' => $reviewId->value(),
            'snippet' => $body !== null ? mb_substr(trim($body), 0, 140) : null,
        ];
    }
}
