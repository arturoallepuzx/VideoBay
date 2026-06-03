<?php

declare(strict_types=1);

namespace App\Inventory\Application\ProposeSaleToStore;

use App\Inventory\Domain\Entity\SaleProposal;

final readonly class ProposeSaleToStoreResponse
{
    private function __construct(
        public string $id,
        public string $userId,
        public ?string $movieId,
        public ?string $titleText,
        public ?string $barcode,
        public string $format,
        public string $condition,
        public ?string $notes,
        public ?int $offeredPriceCents,
        public ?string $currency,
        public string $status,
        public string $createdAt,
    ) {}

    public static function create(SaleProposal $proposal): self
    {
        return new self(
            id: $proposal->id()->value(),
            userId: $proposal->userId()->value(),
            movieId: $proposal->movieId()?->value(),
            titleText: $proposal->titleText(),
            barcode: $proposal->barcode()?->value(),
            format: $proposal->format()->value(),
            condition: $proposal->condition()->value(),
            notes: $proposal->notes(),
            offeredPriceCents: $proposal->offeredPrice()?->cents(),
            currency: $proposal->offeredPrice()?->currency(),
            status: $proposal->status()->value(),
            createdAt: $proposal->createdAt()->value()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'movie_id' => $this->movieId,
            'title_text' => $this->titleText,
            'barcode' => $this->barcode,
            'format' => $this->format,
            'condition' => $this->condition,
            'notes' => $this->notes,
            'offered_price_cents' => $this->offeredPriceCents,
            'currency' => $this->currency,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }
}
