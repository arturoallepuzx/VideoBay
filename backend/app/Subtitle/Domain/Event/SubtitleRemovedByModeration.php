<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Event;

use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\ValueObject\SubtitleLabel;
use App\Subtitle\Domain\ValueObject\SubtitleLanguage;

class SubtitleRemovedByModeration
{
    private function __construct(
        private Uuid $subtitleId,
        private Uuid $uploadedByUserId,
        private Uuid $movieId,
        private SubtitleLanguage $language,
        private SubtitleLabel $label,
    ) {}

    public static function create(
        Uuid $subtitleId,
        Uuid $uploadedByUserId,
        Uuid $movieId,
        SubtitleLanguage $language,
        SubtitleLabel $label,
    ): self {
        return new self($subtitleId, $uploadedByUserId, $movieId, $language, $label);
    }

    public function subtitleId(): Uuid
    {
        return $this->subtitleId;
    }

    public function uploadedByUserId(): Uuid
    {
        return $this->uploadedByUserId;
    }

    public function movieId(): Uuid
    {
        return $this->movieId;
    }

    public function language(): SubtitleLanguage
    {
        return $this->language;
    }

    public function label(): SubtitleLabel
    {
        return $this->label;
    }
}
