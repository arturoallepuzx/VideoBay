<?php

declare(strict_types=1);

namespace App\Order\Application\CreateCheckoutSession;

use App\Order\Domain\Entity\CartItem;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Entity\OrderItem;
use App\Order\Domain\Exception\CartHasUnavailableItemsException;
use App\Order\Domain\Exception\EmptyCartException;
use App\Order\Domain\Interfaces\CartRepositoryInterface;
use App\Order\Domain\Interfaces\CheckoutGatewayInterface;
use App\Order\Domain\Interfaces\CopyDetailsProviderInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Interfaces\StockReservationInterface;
use App\Order\Domain\ValueObject\CheckoutLineItem;
use App\Shared\Domain\Interfaces\SystemCurrencyProviderInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class CreateCheckoutSession
{
    private string $currency;

    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private CopyDetailsProviderInterface $copyDetailsProvider,
        private OrderRepositoryInterface $orderRepository,
        private StockReservationInterface $stockReservation,
        private CheckoutGatewayInterface $checkoutGateway,
        private TransactionRunnerInterface $transactionRunner,
        private int $checkoutTtlMinutes,
        private string $frontendUrl,
        SystemCurrencyProviderInterface $currencyProvider,
    ) {
        $this->currency = $currencyProvider->getCurrency();
    }

    public function __invoke(string $userUuid): CreateCheckoutSessionResponse
    {
        $userId = Uuid::create($userUuid);

        $cart = $this->cartRepository->findByUserId($userId);
        if ($cart === null || $cart->isEmpty()) {
            throw EmptyCartException::cannotCheckout();
        }

        $copyIds = array_map(fn (CartItem $item): Uuid => $item->physicalCopyId(), $cart->items());
        $details = $this->copyDetailsProvider->getByIds($copyIds);

        $orderItems = [];
        $lineItems = [];

        foreach ($cart->items() as $item) {
            $copyId = $item->physicalCopyId();
            $quantity = $item->quantity();
            $detail = $details[$copyId->value()] ?? null;

            if ($detail === null || ! $detail->canFulfill($quantity->value())) {
                throw CartHasUnavailableItemsException::cannotCheckout();
            }

            $orderItems[] = OrderItem::create(
                $copyId,
                $quantity,
                $detail->priceCents(),
                $detail->movieTitle(),
                $detail->format(),
                $detail->condition(),
            );

            $lineItems[] = CheckoutLineItem::create(
                sprintf('%s (%s, %s)', $detail->movieTitle(), $detail->format(), $detail->condition()),
                $detail->priceCents(),
                $quantity->value(),
            );
        }

        $expiresAt = DomainDateTime::now()->value()->modify(sprintf('+%d minutes', $this->checkoutTtlMinutes));
        $order = Order::dddCreate($userId, $orderItems, DomainDateTime::create($expiresAt));

        $session = $this->checkoutGateway->createSession(
            $lineItems,
            $this->currency,
            $order->id()->value(),
            $this->frontendUrl.'/orders/success?session_id={CHECKOUT_SESSION_ID}',
            $this->frontendUrl.'/cart',
            $expiresAt,
        );

        $order->attachStripeSession($session->sessionId());

        $this->transactionRunner->run(function () use ($order): void {
            foreach ($order->items() as $item) {
                $this->stockReservation->reserve($item->physicalCopyId(), $item->quantity()->value());
            }

            $this->orderRepository->create($order);
        });

        return CreateCheckoutSessionResponse::create($order, $session);
    }
}
