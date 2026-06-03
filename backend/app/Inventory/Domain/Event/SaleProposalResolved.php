<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Event;

use App\Inventory\Domain\ValueObject\CopyFormat;
use App\Inventory\Domain\ValueObject\SaleProposalStatus;
use App\Shared\Domain\ValueObject\Uuid;

class SaleProposalResolved
{
    private function __construct(
        private Uuid $proposalId,
        private Uuid $userId,
        private SaleProposalStatus $status,
        private ?Uuid $movieId,
        private ?string $titleText,
        private CopyFormat $format,
    ) {}

    public static function create(
        Uuid $proposalId,
        Uuid $userId,
        SaleProposalStatus $status,
        ?Uuid $movieId,
        ?string $titleText,
        CopyFormat $format,
    ): self {
        return new self($proposalId, $userId, $status, $movieId, $titleText, $format);
    }

    public function proposalId(): Uuid
    {
        return $this->proposalId;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function status(): SaleProposalStatus
    {
        return $this->status;
    }

    public function movieId(): ?Uuid
    {
        return $this->movieId;
    }

    public function titleText(): ?string
    {
        return $this->titleText;
    }

    public function format(): CopyFormat
    {
        return $this->format;
    }
}
