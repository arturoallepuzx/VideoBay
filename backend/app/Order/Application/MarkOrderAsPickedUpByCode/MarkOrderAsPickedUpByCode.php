<?php

declare(strict_types=1);

namespace App\Order\Application\MarkOrderAsPickedUpByCode;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\PickupCode;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;

class MarkOrderAsPickedUpByCode
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private TransactionRunnerInterface $transactionRunner,
    ) {}

    public function __invoke(string $pickupCode): MarkOrderAsPickedUpByCodeResponse
    {
        $code = PickupCode::create($pickupCode);

        $order = $this->transactionRunner->run(function () use ($code) {
            $order = $this->orderRepository->findByPickupCodeForUpdate($code);

            if ($order === null) {
                throw OrderNotFoundException::forPickupCode($code);
            }

            $order->markPickedUp();
            $this->orderRepository->update($order);

            return $order;
        });

        return MarkOrderAsPickedUpByCodeResponse::create($order);
    }
}
