<?php

declare(strict_types=1);

namespace App\Subtitle\Application\UploadUserSubtitle;

use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Subtitle\Domain\Entity\Subtitle;
use App\Subtitle\Domain\Interfaces\SubtitleConverterInterface;
use App\Subtitle\Domain\Interfaces\SubtitleFileStorageInterface;
use App\Subtitle\Domain\Interfaces\SubtitleMovieResolverInterface;
use App\Subtitle\Domain\Interfaces\SubtitleRepositoryInterface;
use App\Subtitle\Domain\ValueObject\SubtitleFormat;
use App\Subtitle\Domain\ValueObject\SubtitleLabel;
use App\Subtitle\Domain\ValueObject\SubtitleLanguage;
use App\Subtitle\Domain\ValueObject\SubtitleSource;

class UploadUserSubtitle
{
    public function __construct(
        private SubtitleRepositoryInterface $subtitleRepository,
        private SubtitleMovieResolverInterface $movieResolver,
        private SubtitleConverterInterface $converter,
        private SubtitleFileStorageInterface $storage,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(
        string $movieUuid,
        string $uploadedByUserUuid,
        bool $uploadedByAdmin,
        string $temporaryPath,
        string $originalFilename,
        string $language,
        ?string $label,
    ): UploadUserSubtitleResponse {
        $movie = $this->movieResolver->resolve(Uuid::create($movieUuid));
        $userId = Uuid::create($uploadedByUserUuid);
        $languageVo = SubtitleLanguage::create($language);
        $format = SubtitleFormat::create((string) pathinfo($originalFilename, PATHINFO_EXTENSION));
        $contents = file_get_contents($temporaryPath);

        if ($contents === false || trim($contents) === '') {
            throw new \InvalidArgumentException('Subtitle upload cannot be empty.');
        }

        $webVtt = $this->converter->toWebVtt($contents, $format);
        $subtitleId = Uuid::generate();
        $filePath = $this->storage->store($subtitleId, $webVtt);

        $subtitle = Subtitle::dddCreate(
            $subtitleId,
            $movie->movieId(),
            $languageVo,
            SubtitleLabel::create($label !== null && trim($label) !== '' ? $label : strtoupper($languageVo->value())),
            $uploadedByAdmin ? SubtitleSource::adminUpload() : SubtitleSource::userUpload(),
            null,
            null,
            $filePath,
            $format,
            $userId,
        );

        try {
            $this->transactionRunner->run(function () use ($subtitle): void {
                $this->subtitleRepository->create($subtitle);
            });
        } catch (\Throwable $e) {
            $this->storage->delete($filePath);

            throw $e;
        }

        return UploadUserSubtitleResponse::create($subtitle);
    }
}
