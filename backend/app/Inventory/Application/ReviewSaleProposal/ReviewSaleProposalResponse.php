<?php

declare(strict_types=1);

namespace App\Inventory\Application\ReviewSaleProposal;

use App\Inventory\Domain\Entity\SaleProposal;

final readonly class ReviewSaleProposalResponse
{
    private function __construct(
        public string $id,
        public string $status,
        public string $updatedAt,
    ) {}

    public static function create(SaleProposal $proposal): self
    {
        return new self(
            id: $proposal->id()->value(),
            status: $proposal->status()->value(),
            updatedAt: $proposal->updatedAt()->value()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'updated_at' => $this->updatedAt,
        ];
    }
}
