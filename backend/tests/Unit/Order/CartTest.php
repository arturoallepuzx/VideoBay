<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Order\Domain\Entity\Cart;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    public function test_ddd_create_starts_empty(): void
    {
        $cart = Cart::dddCreate(Uuid::generate());

        $this->assertTrue($cart->isEmpty());
        $this->assertSame([], $cart->items());
        $this->assertFalse($cart->wasModified());
    }

    public function test_add_item_appends_when_new_copy(): void
    {
        $cart = Cart::dddCreate(Uuid::generate());
        $copyId = Uuid::generate();

        $cart->addItem($copyId, Quantity::create(1));

        $this->assertCount(1, $cart->items());
        $this->assertTrue($cart->items()[0]->physicalCopyId()->equals($copyId));
        $this->assertSame(1, $cart->items()[0]->quantity()->value());
        $this->assertTrue($cart->wasModified());
    }

    public function test_add_item_increments_quantity_for_existing_copy(): void
    {
        $cart = Cart::dddCreate(Uuid::generate());
        $copyId = Uuid::generate();

        $cart->addItem($copyId, Quantity::create(2));
        $cart->addItem($copyId, Quantity::create(3));

        $this->assertCount(1, $cart->items());
        $this->assertSame(5, $cart->items()[0]->quantity()->value());
    }

    public function test_update_item_quantity_changes_existing(): void
    {
        $cart = Cart::dddCreate(Uuid::generate());
        $copyId = Uuid::generate();
        $cart->addItem($copyId, Quantity::create(1));

        $cart->updateItemQuantity($copyId, Quantity::create(7));

        $this->assertSame(7, $cart->items()[0]->quantity()->value());
    }

    public function test_remove_item_drops_existing_copy(): void
    {
        $cart = Cart::dddCreate(Uuid::generate());
        $copyId = Uuid::generate();
        $cart->addItem($copyId, Quantity::create(1));

        $cart->removeItem($copyId);

        $this->assertTrue($cart->isEmpty());
    }

    public function test_remove_unknown_item_is_noop(): void
    {
        $cart = Cart::dddCreate(Uuid::generate());
        $cart->addItem(Uuid::generate(), Quantity::create(1));

        $cart->removeItem(Uuid::generate());

        $this->assertCount(1, $cart->items());
    }
}
