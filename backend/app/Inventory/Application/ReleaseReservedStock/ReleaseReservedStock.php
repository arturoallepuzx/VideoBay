<?php

declare(strict_types=1);

namespace App\Inventory\Application\ReleaseReservedStock;

use App\Inventory\Domain\Exception\PhysicalCopyNotFoundException;
use App\Inventory\Domain\Interfaces\PhysicalCopyRepositoryInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;

class ReleaseReservedStock
{
    public function __construct(
        private PhysicalCopyRepositoryInterface $physicalCopyRepository,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(string $copyUuid, int $quantity): ReleaseReservedStockResponse
    {
        $uuid = Uuid::create($copyUuid);

        $copy = $this->transactionRunner->run(function () use ($uuid, $quantity) {
            $copy = $this->physicalCopyRepository->findByUuidForUpdate($uuid);

            if ($copy === null) {
                throw PhysicalCopyNotFoundException::forUuid($uuid);
            }

            $copy->releaseReservedStock($quantity);
            $this->physicalCopyRepository->update($copy);

            return $copy;
        });

        return ReleaseReservedStockResponse::create($copy);
    }
}
