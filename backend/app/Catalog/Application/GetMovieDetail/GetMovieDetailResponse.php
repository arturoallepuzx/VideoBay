<?php

declare(strict_types=1);

namespace App\Catalog\Application\GetMovieDetail;

use App\Catalog\Domain\Entity\Genre;
use App\Catalog\Domain\Entity\Movie;
use App\Catalog\Domain\Entity\MovieCredit;
use App\Catalog\Domain\Entity\Person;

final readonly class GetMovieDetailResponse
{
    /**
     * @param  list<array<string, mixed>>  $genres
     * @param  list<array<string, mixed>>  $credits
     */
    private function __construct(
        public string $uuid,
        public ?int $tmdbId,
        public ?string $imdbId,
        public string $title,
        public ?string $originalTitle,
        public ?string $overview,
        public ?string $releaseDate,
        public ?int $runtimeMinutes,
        public ?string $originalLanguage,
        public ?string $posterPath,
        public ?string $backdropPath,
        public ?float $tmdbRating,
        public array $genres,
        public array $credits,
        public ?string $videoFileId,
    ) {}

    /**
     * @param  list<Genre>  $genres
     * @param  list<MovieCredit>  $credits
     * @param  array<string, Person>  $peopleByUuid
     */
    public static function create(Movie $movie, array $genres, array $credits, array $peopleByUuid, ?string $videoFileId = null): self
    {
        $genresOut = array_map(
            fn (Genre $g): array => [
                'tmdb_id' => $g->tmdbId()?->value(),
                'name' => $g->name()->value(),
            ],
            $genres,
        );

        $creditsOut = array_map(
            function (MovieCredit $c) use ($peopleByUuid): array {
                $person = $peopleByUuid[$c->personId()->value()] ?? null;

                return [
                    'person_uuid' => $c->personId()->value(),
                    'person_tmdb_id' => $person?->tmdbId()?->value(),
                    'person_name' => $person?->name()->value(),
                    'person_profile_path' => $person?->profilePath()?->value(),
                    'department' => $c->department()->value(),
                    'job' => $c->job(),
                    'character_name' => $c->characterName(),
                    'credit_order' => $c->creditOrder(),
                ];
            },
            $credits,
        );

        return new self(
            uuid: $movie->id()->value(),
            tmdbId: $movie->tmdbId()?->value(),
            imdbId: $movie->imdbId()?->value(),
            title: $movie->title()->value(),
            originalTitle: $movie->originalTitle()?->value(),
            overview: $movie->overview()?->value(),
            releaseDate: $movie->releaseDate()?->format('Y-m-d'),
            runtimeMinutes: $movie->runtimeMinutes()?->value(),
            originalLanguage: $movie->originalLanguage()?->value(),
            posterPath: $movie->posterPath()?->value(),
            backdropPath: $movie->backdropPath()?->value(),
            tmdbRating: $movie->tmdbRating()?->value(),
            genres: array_values($genresOut),
            credits: array_values($creditsOut),
            videoFileId: $videoFileId,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'tmdb_id' => $this->tmdbId,
            'imdb_id' => $this->imdbId,
            'title' => $this->title,
            'original_title' => $this->originalTitle,
            'overview' => $this->overview,
            'release_date' => $this->releaseDate,
            'runtime_minutes' => $this->runtimeMinutes,
            'original_language' => $this->originalLanguage,
            'poster_path' => $this->posterPath,
            'backdrop_path' => $this->backdropPath,
            'tmdb_rating' => $this->tmdbRating,
            'genres' => $this->genres,
            'credits' => $this->credits,
            'video_file_id' => $this->videoFileId,
            'streamable' => $this->videoFileId !== null,
        ];
    }
}
