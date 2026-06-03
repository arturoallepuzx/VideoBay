<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\Interfaces;

use App\Subtitle\Domain\ValueObject\ExternalSubtitleCandidate;
use App\Subtitle\Domain\ValueObject\ExternalSubtitleDownload;
use App\Subtitle\Domain\ValueObject\SubtitleLanguage;
use App\Subtitle\Domain\ValueObject\SubtitleMovieMetadata;

interface ExternalSubtitleProviderInterface
{
    /**
     * @return list<ExternalSubtitleCandidate>
     */
    public function search(SubtitleMovieMetadata $movie, ?SubtitleLanguage $language): array;

    public function download(ExternalSubtitleCandidate $candidate): ExternalSubtitleDownload;
}
