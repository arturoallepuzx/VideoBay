<?php

declare(strict_types=1);

namespace App\Subtitle\Application\SearchExternalSubtitles;

use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\Interfaces\ExternalSubtitleProviderInterface;
use App\Subtitle\Domain\Interfaces\SubtitleMovieResolverInterface;
use App\Subtitle\Domain\ValueObject\SubtitleLanguage;

class SearchExternalSubtitles
{
    public function __construct(
        private SubtitleMovieResolverInterface $movieResolver,
        private ExternalSubtitleProviderInterface $externalSubtitleProvider,
    ) {}

    public function __invoke(string $movieUuid, ?string $language): SearchExternalSubtitlesResponse
    {
        $movie = $this->movieResolver->resolve(Uuid::create($movieUuid));
        $languageVo = $language !== null && trim($language) !== '' ? SubtitleLanguage::create($language) : null;

        return SearchExternalSubtitlesResponse::create($this->externalSubtitleProvider->search($movie, $languageVo));
    }
}
