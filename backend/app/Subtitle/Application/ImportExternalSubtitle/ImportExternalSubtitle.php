<?php

declare(strict_types=1);

namespace App\Subtitle\Application\ImportExternalSubtitle;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\Entity\Subtitle;
use App\Subtitle\Domain\Exception\SubtitleAlreadyRemovedException;
use App\Subtitle\Domain\Interfaces\ExternalSubtitleProviderInterface;
use App\Subtitle\Domain\Interfaces\SubtitleConverterInterface;
use App\Subtitle\Domain\Interfaces\SubtitleFileStorageInterface;
use App\Subtitle\Domain\Interfaces\SubtitleMovieResolverInterface;
use App\Subtitle\Domain\Interfaces\SubtitleRepositoryInterface;
use App\Subtitle\Domain\ValueObject\ExternalSubtitleCandidate;
use App\Subtitle\Domain\ValueObject\SubtitleLabel;
use App\Subtitle\Domain\ValueObject\SubtitleLanguage;
use App\Subtitle\Domain\ValueObject\SubtitleSource;

class ImportExternalSubtitle
{
    public function __construct(
        private SubtitleRepositoryInterface $subtitleRepository,
        private SubtitleMovieResolverInterface $movieResolver,
        private ExternalSubtitleProviderInterface $externalSubtitleProvider,
        private SubtitleConverterInterface $converter,
        private SubtitleFileStorageInterface $storage,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(
        string $movieUuid,
        string $importedByUserUuid,
        string $provider,
        string $externalId,
        string $fileId,
        string $language,
        string $label,
    ): ImportExternalSubtitleResponse {
        $normalizedProvider = strtolower(trim($provider));
        $normalizedExternalId = trim($externalId);

        $existing = $this->subtitleRepository->findByProviderAndExternalId($normalizedProvider, $normalizedExternalId);
        if ($existing !== null) {
            if ($existing->isDeleted()) {
                throw SubtitleAlreadyRemovedException::forExternal($normalizedProvider, $normalizedExternalId);
            }

            return ImportExternalSubtitleResponse::create($existing);
        }

        $movie = $this->movieResolver->resolve(Uuid::create($movieUuid));
        $importedByUserId = Uuid::create($importedByUserUuid);
        $candidate = ExternalSubtitleCandidate::create(
            $normalizedProvider,
            $normalizedExternalId,
            $fileId,
            SubtitleLanguage::create($language),
            SubtitleLabel::create($label),
        );

        $download = $this->externalSubtitleProvider->download($candidate);
        $webVtt = $this->converter->toWebVtt($download->contents(), $download->format());
        $subtitleId = Uuid::generate();
        $filePath = $this->storage->store($subtitleId, $webVtt);

        $subtitle = Subtitle::dddCreate(
            $subtitleId,
            $movie->movieId(),
            $candidate->language(),
            $candidate->label(),
            SubtitleSource::external(),
            $candidate->provider(),
            $candidate->externalId(),
            $filePath,
            $download->format(),
            $importedByUserId,
        );

        try {
            $this->transactionRunner->run(function () use ($subtitle): void {
                $this->subtitleRepository->create($subtitle);
            });
        } catch (\Throwable $e) {
            $this->storage->delete($filePath);

            throw $e;
        }

        return ImportExternalSubtitleResponse::create($subtitle);
    }
}
