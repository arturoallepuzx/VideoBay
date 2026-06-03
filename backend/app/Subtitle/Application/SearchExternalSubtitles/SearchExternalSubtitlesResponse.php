<?php

declare(strict_types=1);

namespace App\Subtitle\Application\SearchExternalSubtitles;

use App\Subtitle\Domain\ValueObject\ExternalSubtitleCandidate;

final readonly class SearchExternalSubtitlesResponse
{
    /** @param list<array<string, mixed>> $items */
    private function __construct(public array $items) {}

    /** @param list<ExternalSubtitleCandidate> $candidates */
    public static function create(array $candidates): self
    {
        return new self(array_values(array_map(
            fn (ExternalSubtitleCandidate $candidate): array => [
                'provider' => $candidate->provider(),
                'external_id' => $candidate->externalId(),
                'file_id' => $candidate->fileId(),
                'language' => $candidate->language()->value(),
                'label' => $candidate->label()->value(),
                'download_count' => $candidate->downloadCount(),
                'hearing_impaired' => $candidate->hearingImpaired(),
            ],
            $candidates,
        )));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['items' => $this->items];
    }
}
