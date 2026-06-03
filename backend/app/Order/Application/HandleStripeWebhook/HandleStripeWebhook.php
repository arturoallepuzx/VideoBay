<?php

declare(strict_types=1);

namespace App\Order\Application\HandleStripeWebhook;

use App\Order\Domain\Event\OrderReadyForPickup;
use App\Order\Domain\Interfaces\CartRepositoryInterface;
use App\Order\Domain\Interfaces\CheckoutGatewayInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Interfaces\StockReservationInterface;
use App\Order\Domain\Interfaces\StripeWebhookEventStoreInterface;
use App\Order\Domain\ValueObject\PickupCode;
use App\Order\Domain\ValueObject\WebhookEvent;
use App\Shared\Domain\Interfaces\DomainEventDispatcherInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;

class HandleStripeWebhook
{
    private const CHECKOUT_COMPLETED = 'checkout.session.completed';

    public function __construct(
        private CheckoutGatewayInterface $checkoutGateway,
        private StripeWebhookEventStoreInterface $webhookEventStore,
        private OrderRepositoryInterface $orderRepository,
        private StockReservationInterface $stockReservation,
        private CartRepositoryInterface $cartRepository,
        private TransactionRunnerInterface $transactionRunner,
        private DomainEventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(string $payload, string $signature): void
    {
        $event = $this->checkoutGateway->parseWebhookEvent($payload, $signature);

        if (! $this->webhookEventStore->shouldProcess($event)) {
            return;
        }

        try {
            if ($event->type() === self::CHECKOUT_COMPLETED && $event->sessionId() !== null) {
                $this->fulfill($event);
            }

            $this->webhookEventStore->markProcessed($event->id());
        } catch (\Throwable $e) {
            $this->webhookEventStore->markFailed($event->id(), $e->getMessage());

            throw $e;
        }
    }

    private function fulfill(WebhookEvent $event): void
    {
        $this->transactionRunner->run(function () use ($event): void {
            $order = $this->orderRepository->findByStripeSessionIdForUpdate((string) $event->sessionId());

            if ($order === null || ! $order->status()->isPendingPayment()) {
                return;
            }

            $order->markPaid((string) $event->paymentIntentId());
            $pickupCode = $this->generateUniquePickupCode();
            $order->markReadyForPickup($pickupCode);

            foreach ($order->items() as $item) {
                $this->stockReservation->confirmSale($item->physicalCopyId(), $item->quantity()->value());
            }

            $this->orderRepository->update($order);
            $this->eventDispatcher->dispatch(OrderReadyForPickup::create(
                $order->id(),
                $order->userId(),
                $pickupCode,
                $order->totalCents(),
            ));

            $cart = $this->cartRepository->findByUserId($order->userId());
            if ($cart !== null) {
                $this->cartRepository->delete($cart);
            }
        });
    }

    private function generateUniquePickupCode(): PickupCode
    {
        do {
            $pickupCode = PickupCode::generate();
        } while ($this->orderRepository->existsByPickupCode($pickupCode));

        return $pickupCode;
    }
}
