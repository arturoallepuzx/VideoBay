<?php

declare(strict_types=1);

namespace App\Subtitle\Application\ListMovieSubtitles;

use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\Interfaces\SubtitleMovieResolverInterface;
use App\Subtitle\Domain\Interfaces\SubtitleRepositoryInterface;

class ListMovieSubtitles
{
    public function __construct(
        private SubtitleRepositoryInterface $subtitleRepository,
        private SubtitleMovieResolverInterface $movieResolver,
    ) {}

    public function __invoke(string $movieUuid): ListMovieSubtitlesResponse
    {
        $movieId = Uuid::create($movieUuid);
        $this->movieResolver->resolve($movieId);

        return ListMovieSubtitlesResponse::create($this->subtitleRepository->listByMovie($movieId));
    }
}
