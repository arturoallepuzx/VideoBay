<?php

declare(strict_types=1);

namespace App\Subtitle\Domain\ValueObject;

class ExternalSubtitleCandidate
{
    private function __construct(
        private string $provider,
        private string $externalId,
        private string $fileId,
        private SubtitleLanguage $language,
        private SubtitleLabel $label,
        private ?int $downloadCount,
        private bool $hearingImpaired,
    ) {}

    public static function create(
        string $provider,
        string $externalId,
        string $fileId,
        SubtitleLanguage $language,
        SubtitleLabel $label,
        ?int $downloadCount = null,
        bool $hearingImpaired = false,
    ): self {
        $normalizedProvider = strtolower(trim($provider));
        $normalizedExternalId = trim($externalId);
        $normalizedFileId = trim($fileId);

        if ($normalizedProvider === '' || $normalizedExternalId === '' || $normalizedFileId === '') {
            throw new \InvalidArgumentException('External subtitle candidate identifiers cannot be empty.');
        }

        return new self(
            $normalizedProvider,
            $normalizedExternalId,
            $normalizedFileId,
            $language,
            $label,
            $downloadCount,
            $hearingImpaired,
        );
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function externalId(): string
    {
        return $this->externalId;
    }

    public function fileId(): string
    {
        return $this->fileId;
    }

    public function language(): SubtitleLanguage
    {
        return $this->language;
    }

    public function label(): SubtitleLabel
    {
        return $this->label;
    }

    public function downloadCount(): ?int
    {
        return $this->downloadCount;
    }

    public function hearingImpaired(): bool
    {
        return $this->hearingImpaired;
    }
}
