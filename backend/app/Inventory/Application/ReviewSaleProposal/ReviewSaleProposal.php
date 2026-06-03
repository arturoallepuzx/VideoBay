<?php

declare(strict_types=1);

namespace App\Inventory\Application\ReviewSaleProposal;

use App\Inventory\Domain\Event\SaleProposalAccepted;
use App\Inventory\Domain\Event\SaleProposalResolved;
use App\Inventory\Domain\Exception\SaleProposalNotFoundException;
use App\Inventory\Domain\Interfaces\SaleProposalRepositoryInterface;
use App\Shared\Domain\Interfaces\DomainEventDispatcherInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;

class ReviewSaleProposal
{
    public const DECISION_ACCEPT = 'accept';

    public const DECISION_REJECT = 'reject';

    public function __construct(
        private SaleProposalRepositoryInterface $saleProposalRepository,
        private TransactionRunnerInterface $transactionRunner,
        private DomainEventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(string $proposalUuid, string $decision): ReviewSaleProposalResponse
    {
        if (! in_array($decision, [self::DECISION_ACCEPT, self::DECISION_REJECT], true)) {
            throw new \InvalidArgumentException(
                sprintf('Decision must be "%s" or "%s".', self::DECISION_ACCEPT, self::DECISION_REJECT)
            );
        }

        $uuid = Uuid::create($proposalUuid);

        $proposal = $this->transactionRunner->run(function () use ($uuid, $decision) {
            $proposal = $this->saleProposalRepository->findByUuidForUpdate($uuid);

            if ($proposal === null) {
                throw SaleProposalNotFoundException::forUuid($uuid);
            }

            if ($decision === self::DECISION_ACCEPT) {
                $proposal->accept();
            } else {
                $proposal->reject();
            }

            $this->saleProposalRepository->update($proposal);

            if ($decision === self::DECISION_ACCEPT
                && $proposal->barcode() !== null
                && $proposal->movieId() !== null) {
                $this->eventDispatcher->dispatch(SaleProposalAccepted::create(
                    $proposal->barcode(),
                    $proposal->movieId(),
                ));
            }

            $this->eventDispatcher->dispatch(SaleProposalResolved::create(
                $proposal->id(),
                $proposal->userId(),
                $proposal->status(),
                $proposal->movieId(),
                $proposal->titleText(),
                $proposal->format(),
            ));

            return $proposal;
        });

        return ReviewSaleProposalResponse::create($proposal);
    }
}
