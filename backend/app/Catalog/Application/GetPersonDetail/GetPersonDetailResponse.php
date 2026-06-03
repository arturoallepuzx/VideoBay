<?php

declare(strict_types=1);

namespace App\Catalog\Application\GetPersonDetail;

use App\Catalog\Domain\Entity\Person;

final readonly class GetPersonDetailResponse
{
    private function __construct(
        public string $uuid,
        public ?int $tmdbId,
        public string $name,
        public ?string $biography,
        public ?string $profilePath,
        public ?string $birthday,
        public ?string $deathday,
        public ?string $placeOfBirth,
    ) {}

    public static function create(Person $person): self
    {
        return new self(
            uuid: $person->id()->value(),
            tmdbId: $person->tmdbId()?->value(),
            name: $person->name()->value(),
            biography: $person->biography()?->value(),
            profilePath: $person->profilePath()?->value(),
            birthday: $person->birthday()?->format('Y-m-d'),
            deathday: $person->deathday()?->format('Y-m-d'),
            placeOfBirth: $person->placeOfBirth(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'tmdb_id' => $this->tmdbId,
            'name' => $this->name,
            'biography' => $this->biography,
            'profile_path' => $this->profilePath,
            'birthday' => $this->birthday,
            'deathday' => $this->deathday,
            'place_of_birth' => $this->placeOfBirth,
        ];
    }
}
