<?php

declare(strict_types=1);

namespace App\Subtitle\Application\UploadUserSubtitle;

use App\Subtitle\Domain\Entity\Subtitle;

final readonly class UploadUserSubtitleResponse
{
    private function __construct(
        public string $uuid,
        public string $language,
        public string $label,
        public string $source,
    ) {}

    public static function create(Subtitle $subtitle): self
    {
        return new self(
            uuid: $subtitle->id()->value(),
            language: $subtitle->language()->value(),
            label: $subtitle->label()->value(),
            source: $subtitle->source()->value(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'language' => $this->language,
            'label' => $this->label,
            'source' => $this->source,
        ];
    }
}
