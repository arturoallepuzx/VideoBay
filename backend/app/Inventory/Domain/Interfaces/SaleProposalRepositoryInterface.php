<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Interfaces;

use App\Inventory\Domain\Entity\SaleProposal;
use App\Shared\Domain\ValueObject\Uuid;

interface SaleProposalRepositoryInterface
{
    public function create(SaleProposal $proposal): void;

    public function update(SaleProposal $proposal): void;

    public function findByUuid(Uuid $uuid): ?SaleProposal;

    public function findByUuidForUpdate(Uuid $uuid): ?SaleProposal;

    /**
     * @return array{proposals: list<SaleProposal>, total: int, page: int, totalPages: int}
     */
    public function listByUser(Uuid $userId, int $page, int $perPage): array;

    /**
     * @return array{proposals: list<SaleProposal>, total: int, page: int, totalPages: int}
     */
    public function listPending(int $page, int $perPage): array;
}
