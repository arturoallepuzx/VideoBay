<?php

declare(strict_types=1);

namespace App\Order\Application\CancelExpiredOrder;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Interfaces\StockReservationInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\DomainDateTime;

class CancelExpiredOrder
{
    private const BATCH_LIMIT = 100;

    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private StockReservationInterface $stockReservation,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(): int
    {
        $expired = $this->orderRepository->findExpired(DomainDateTime::now(), self::BATCH_LIMIT);
        $cancelled = 0;

        foreach ($expired as $order) {
            $wasCancelled = $this->transactionRunner->run(function () use ($order): bool {
                $locked = $this->orderRepository->findByUuidForUpdate($order->id());

                if ($locked === null || ! $locked->status()->isPendingPayment()) {
                    return false;
                }

                foreach ($locked->items() as $item) {
                    $this->stockReservation->release($item->physicalCopyId(), $item->quantity()->value());
                }

                $locked->cancel();
                $this->orderRepository->update($locked);

                return true;
            });

            if ($wasCancelled) {
                $cancelled++;
            }
        }

        return $cancelled;
    }
}
