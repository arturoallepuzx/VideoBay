<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Order\Application\RemoveFromCart\RemoveFromCart;
use App\Order\Domain\Entity\Cart;
use App\Order\Domain\Interfaces\CartRepositoryInterface;
use App\Order\Domain\Interfaces\CopyDetailsProviderInterface;
use App\Order\Domain\ValueObject\CopyDetails;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Closure;
use Mockery;
use Tests\TestCase;

class RemoveFromCartTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function transactionRunner(): TransactionRunnerInterface
    {
        $runner = Mockery::mock(TransactionRunnerInterface::class);
        $runner->shouldReceive('run')->andReturnUsing(fn (Closure $callback) => $callback());

        return $runner;
    }

    private function copyDetails(): CopyDetails
    {
        return CopyDetails::create('movie-uuid', 'The Matrix', 'DVD', 'good', 999, 5, true);
    }

    public function test_removes_item_and_returns_remaining_cart(): void
    {
        $userId = Uuid::generate()->value();
        $copyA = Uuid::generate()->value();
        $copyB = Uuid::generate()->value();

        $cart = Cart::dddCreate(Uuid::create($userId));
        $cart->addItem(Uuid::create($copyA), Quantity::create(1));
        $cart->addItem(Uuid::create($copyB), Quantity::create(2));

        $cartRepository = Mockery::mock(CartRepositoryInterface::class);
        $cartRepository->shouldReceive('findByUserIdForUpdate')->once()->andReturn($cart);
        $cartRepository->shouldReceive('save')->once();

        $provider = Mockery::mock(CopyDetailsProviderInterface::class);
        $provider->shouldReceive('getByIds')->andReturn([$copyB => $this->copyDetails()]);

        $response = (new RemoveFromCart($cartRepository, $provider, $this->transactionRunner()))($userId, $copyA);

        $this->assertCount(1, $response->items);
        $this->assertSame($copyB, $response->items[0]['physical_copy_id']);
    }

    public function test_returns_empty_when_cart_becomes_empty(): void
    {
        $userId = Uuid::generate()->value();
        $copyA = Uuid::generate()->value();

        $cart = Cart::dddCreate(Uuid::create($userId));
        $cart->addItem(Uuid::create($copyA), Quantity::create(1));

        $cartRepository = Mockery::mock(CartRepositoryInterface::class);
        $cartRepository->shouldReceive('findByUserIdForUpdate')->once()->andReturn($cart);
        $cartRepository->shouldReceive('save')->once();

        $provider = Mockery::mock(CopyDetailsProviderInterface::class);
        $provider->shouldNotReceive('getByIds');

        $response = (new RemoveFromCart($cartRepository, $provider, $this->transactionRunner()))($userId, $copyA);

        $this->assertSame([], $response->items);
        $this->assertSame(0, $response->totalCents);
    }

    public function test_returns_empty_when_no_cart_exists(): void
    {
        $userId = Uuid::generate()->value();
        $copyId = Uuid::generate()->value();

        $cartRepository = Mockery::mock(CartRepositoryInterface::class);
        $cartRepository->shouldReceive('findByUserIdForUpdate')->once()->andReturn(null);
        $cartRepository->shouldNotReceive('save');

        $provider = Mockery::mock(CopyDetailsProviderInterface::class);

        $response = (new RemoveFromCart($cartRepository, $provider, $this->transactionRunner()))($userId, $copyId);

        $this->assertSame([], $response->items);
    }
}
