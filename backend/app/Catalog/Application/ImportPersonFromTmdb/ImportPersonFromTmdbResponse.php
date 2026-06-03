<?php

declare(strict_types=1);

namespace App\Catalog\Application\ImportPersonFromTmdb;

use App\Catalog\Domain\Entity\Person;

final readonly class ImportPersonFromTmdbResponse
{
    private function __construct(
        public string $uuid,
        public ?int $tmdbId,
        public string $name,
    ) {}

    public static function create(Person $person): self
    {
        return new self(
            uuid: $person->id()->value(),
            tmdbId: $person->tmdbId()?->value(),
            name: $person->name()->value(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'tmdb_id' => $this->tmdbId,
            'name' => $this->name,
        ];
    }
}
