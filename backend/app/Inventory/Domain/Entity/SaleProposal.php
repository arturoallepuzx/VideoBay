<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entity;

use App\Inventory\Domain\Exception\SaleProposalAlreadyDecidedException;
use App\Inventory\Domain\ValueObject\CopyCondition;
use App\Inventory\Domain\ValueObject\CopyFormat;
use App\Inventory\Domain\ValueObject\SaleProposalStatus;
use App\Shared\Domain\ValueObject\BarcodeValue;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\MoneyAmount;
use App\Shared\Domain\ValueObject\Uuid;

class SaleProposal
{
    private bool $modified = false;

    private function __construct(
        private Uuid $id,
        private Uuid $userId,
        private ?Uuid $movieId,
        private ?string $titleText,
        private ?BarcodeValue $barcode,
        private CopyFormat $format,
        private CopyCondition $condition,
        private ?string $notes,
        private ?MoneyAmount $offeredPrice,
        private SaleProposalStatus $status,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $userId,
        ?Uuid $movieId,
        ?string $titleText,
        ?BarcodeValue $barcode,
        CopyFormat $format,
        CopyCondition $condition,
        ?string $notes,
        ?MoneyAmount $offeredPrice,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $userId,
            $movieId,
            $titleText,
            $barcode,
            $format,
            $condition,
            $notes,
            $offeredPrice,
            SaleProposalStatus::proposed(),
            $now,
            $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $userId,
        ?string $movieId,
        ?string $titleText,
        ?string $barcode,
        string $format,
        string $condition,
        ?string $notes,
        ?int $offeredPriceCents,
        string $currency,
        string $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            Uuid::create($userId),
            $movieId !== null ? Uuid::create($movieId) : null,
            $titleText,
            $barcode !== null ? BarcodeValue::create($barcode) : null,
            CopyFormat::create($format),
            CopyCondition::create($condition),
            $notes,
            $offeredPriceCents !== null ? MoneyAmount::create($offeredPriceCents, $currency) : null,
            SaleProposalStatus::create($status),
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function accept(): void
    {
        if (! $this->status->isProposed()) {
            throw SaleProposalAlreadyDecidedException::forAccept($this->id, $this->status);
        }

        $this->status = SaleProposalStatus::accepted();
        $this->touch();
    }

    public function reject(): void
    {
        if (! $this->status->isProposed()) {
            throw SaleProposalAlreadyDecidedException::forReject($this->id, $this->status);
        }

        $this->status = SaleProposalStatus::rejected();
        $this->touch();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function movieId(): ?Uuid
    {
        return $this->movieId;
    }

    public function titleText(): ?string
    {
        return $this->titleText;
    }

    public function barcode(): ?BarcodeValue
    {
        return $this->barcode;
    }

    public function format(): CopyFormat
    {
        return $this->format;
    }

    public function condition(): CopyCondition
    {
        return $this->condition;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function offeredPrice(): ?MoneyAmount
    {
        return $this->offeredPrice;
    }

    public function status(): SaleProposalStatus
    {
        return $this->status;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function wasModified(): bool
    {
        return $this->modified;
    }

    private function touch(): void
    {
        $this->modified = true;
        $this->updatedAt = DomainDateTime::now();
    }
}
