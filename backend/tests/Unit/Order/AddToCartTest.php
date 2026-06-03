<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Order\Application\AddToCart\AddToCart;
use App\Order\Domain\Entity\Cart;
use App\Order\Domain\Exception\CopyNotPurchasableException;
use App\Order\Domain\Interfaces\CartRepositoryInterface;
use App\Order\Domain\Interfaces\CopyDetailsProviderInterface;
use App\Order\Domain\ValueObject\CopyDetails;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Closure;
use Mockery;
use Tests\TestCase;

class AddToCartTest extends TestCase
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

    private function copyDetails(int $stock = 5, bool $active = true): CopyDetails
    {
        return CopyDetails::create('movie-uuid', 'The Matrix', 'DVD', 'good', 999, $stock, $active);
    }

    public function test_adds_item_to_new_cart_when_purchasable(): void
    {
        $userId = Uuid::generate()->value();
        $copyId = Uuid::generate()->value();

        $cartRepository = Mockery::mock(CartRepositoryInterface::class);
        $cartRepository->shouldReceive('findByUserIdForUpdate')->once()->andReturn(null);
        $cartRepository->shouldReceive('save')->once()->with(Mockery::type(Cart::class));

        $provider = Mockery::mock(CopyDetailsProviderInterface::class);
        $provider->shouldReceive('getByIds')->andReturn([$copyId => $this->copyDetails()]);

        $response = (new AddToCart($cartRepository, $provider, $this->transactionRunner()))($userId, $copyId, 2);

        $this->assertCount(1, $response->items);
        $this->assertSame($copyId, $response->items[0]['physical_copy_id']);
        $this->assertSame(2, $response->items[0]['quantity']);
        $this->assertTrue($response->items[0]['available']);
        $this->assertSame(1998, $response->totalCents);
    }

    public function test_throws_when_copy_is_unavailable(): void
    {
        $userId = Uuid::generate()->value();
        $copyId = Uuid::generate()->value();

        $cartRepository = Mockery::mock(CartRepositoryInterface::class);
        $cartRepository->shouldReceive('findByUserIdForUpdate')->andReturn(null);
        $cartRepository->shouldNotReceive('save');

        $provider = Mockery::mock(CopyDetailsProviderInterface::class);
        $provider->shouldReceive('getByIds')->andReturn([$copyId => $this->copyDetails(stock: 0, active: false)]);

        $this->expectException(CopyNotPurchasableException::class);

        (new AddToCart($cartRepository, $provider, $this->transactionRunner()))($userId, $copyId, 1);
    }

    public function test_throws_when_requested_quantity_exceeds_stock(): void
    {
        $userId = Uuid::generate()->value();
        $copyId = Uuid::generate()->value();

        $cartRepository = Mockery::mock(CartRepositoryInterface::class);
        $cartRepository->shouldReceive('findByUserIdForUpdate')->andReturn(null);
        $cartRepository->shouldNotReceive('save');

        $provider = Mockery::mock(CopyDetailsProviderInterface::class);
        $provider->shouldReceive('getByIds')->andReturn([$copyId => $this->copyDetails(stock: 1)]);

        $this->expectException(CopyNotPurchasableException::class);

        (new AddToCart($cartRepository, $provider, $this->transactionRunner()))($userId, $copyId, 5);
    }
}
