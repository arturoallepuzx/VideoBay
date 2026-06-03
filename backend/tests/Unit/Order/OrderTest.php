<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Entity\OrderItem;
use App\Order\Domain\Exception\InvalidOrderTransitionException;
use App\Order\Domain\ValueObject\PickupCode;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    private function makeOrder(?DomainDateTime $expiresAt = null): Order
    {
        $items = [
            OrderItem::create(
                Uuid::generate(),
                Quantity::create(2),
                1000,
                'Inception',
                'BLURAY',
                'new',
            ),
        ];

        return Order::dddCreate(
            Uuid::generate(),
            $items,
            $expiresAt ?? DomainDateTime::create(new \DateTimeImmutable('+30 minutes')),
        );
    }

    public function test_ddd_create_starts_pending_payment_with_total_from_items(): void
    {
        $order = $this->makeOrder();

        $this->assertTrue($order->status()->isPendingPayment());
        $this->assertSame(2000, $order->totalCents());
        $this->assertNull($order->paidAt());
        $this->assertNull($order->pickupCode());
    }

    public function test_attach_stripe_session_sets_id(): void
    {
        $order = $this->makeOrder();

        $order->attachStripeSession('cs_test_abc');

        $this->assertSame('cs_test_abc', $order->stripeSessionId());
    }

    public function test_mark_paid_transitions_from_pending(): void
    {
        $order = $this->makeOrder();

        $order->markPaid('pi_test_123');

        $this->assertTrue($order->status()->isPaid());
        $this->assertSame('pi_test_123', $order->stripePaymentIntentId());
        $this->assertNotNull($order->paidAt());
    }

    public function test_mark_ready_for_pickup_requires_paid_status(): void
    {
        $order = $this->makeOrder();

        $this->expectException(InvalidOrderTransitionException::class);

        $order->markReadyForPickup(PickupCode::generate());
    }

    public function test_full_happy_path_pending_to_picked_up(): void
    {
        $order = $this->makeOrder();
        $code = PickupCode::generate();

        $order->markPaid('pi_test');
        $order->markReadyForPickup($code);
        $order->markPickedUp();

        $this->assertSame('picked_up', $order->status()->value());
        $this->assertSame($code->value(), $order->pickupCode()?->value());
        $this->assertNotNull($order->readyAt());
        $this->assertNotNull($order->pickedUpAt());
    }

    public function test_cancel_transitions_pending_to_cancelled(): void
    {
        $order = $this->makeOrder();

        $order->cancel();

        $this->assertSame('cancelled', $order->status()->value());
        $this->assertNotNull($order->cancelledAt());
    }

    public function test_cancel_throws_when_already_paid(): void
    {
        $order = $this->makeOrder();
        $order->markPaid('pi_test');

        $this->expectException(InvalidOrderTransitionException::class);

        $order->cancel();
    }

    public function test_is_expired_true_when_pending_and_past_expires_at(): void
    {
        $order = $this->makeOrder(DomainDateTime::create(new \DateTimeImmutable('-1 minute')));

        $this->assertTrue($order->isExpired(DomainDateTime::now()));
    }

    public function test_is_expired_false_when_already_paid(): void
    {
        $order = $this->makeOrder(DomainDateTime::create(new \DateTimeImmutable('-1 minute')));
        $order->markPaid('pi_test');

        $this->assertFalse($order->isExpired(DomainDateTime::now()));
    }
}
