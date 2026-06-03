<?php

declare(strict_types=1);

namespace App\Subtitle\Application\ListMovieSubtitles;

use App\Subtitle\Domain\Entity\Subtitle;

final readonly class ListMovieSubtitlesResponse
{
    /** @param list<array<string, mixed>> $items */
    private function __construct(public array $items) {}

    /** @param list<Subtitle> $subtitles */
    public static function create(array $subtitles): self
    {
        $items = array_map(fn (Subtitle $subtitle): array => self::subtitleToArray($subtitle), $subtitles);

        return new self(array_values($items));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['items' => $this->items];
    }

    /** @return array<string, mixed> */
    private static function subtitleToArray(Subtitle $subtitle): array
    {
        return [
            'uuid' => $subtitle->id()->value(),
            'language' => $subtitle->language()->value(),
            'label' => $subtitle->label()->value(),
            'source' => $subtitle->source()->value(),
        ];
    }
}
