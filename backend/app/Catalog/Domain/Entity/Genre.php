<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\ValueObject\GenreName;
use App\Catalog\Domain\ValueObject\TmdbId;

class Genre
{
    private function __construct(
        private ?int $id,
        private ?TmdbId $tmdbId,
        private GenreName $name,
    ) {}

    public static function dddCreate(?TmdbId $tmdbId, GenreName $name): self
    {
        return new self(null, $tmdbId, $name);
    }

    public static function fromPersistence(int $id, ?int $tmdbId, string $name): self
    {
        return new self(
            $id,
            $tmdbId !== null ? TmdbId::create($tmdbId) : null,
            GenreName::create($name),
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function tmdbId(): ?TmdbId
    {
        return $this->tmdbId;
    }

    public function name(): GenreName
    {
        return $this->name;
    }
}
