<?php

declare(strict_types=1);

namespace App\Order\Application\MarkOrderAsPickedUp;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;

class MarkOrderAsPickedUp
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(string $orderUuid): MarkOrderAsPickedUpResponse
    {
        $uuid = Uuid::create($orderUuid);

        $order = $this->transactionRunner->run(function () use ($uuid) {
            $order = $this->orderRepository->findByUuidForUpdate($uuid);

            if ($order === null) {
                throw OrderNotFoundException::forUuid($uuid);
            }

            $order->markPickedUp();
            $this->orderRepository->update($order);

            return $order;
        });

        return MarkOrderAsPickedUpResponse::create($order);
    }
}
