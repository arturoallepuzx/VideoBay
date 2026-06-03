<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Order\Application\UpdateCartItemQuantity\UpdateCartItemQuantity;
use App\Order\Domain\Entity\Cart;
use App\Order\Domain\Exception\CopyNotPurchasableException;
use App\Order\Domain\Interfaces\CartRepositoryInterface;
use App\Order\Domain\Interfaces\CopyDetailsProviderInterface;
use App\Order\Domain\ValueObject\CopyDetails;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Closure;
use Mockery;
use Tests\TestCase;

class UpdateCartItemQuantityTest extends TestCase
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

    private function copyDetails(int $stock = 10, bool $active = true): CopyDetails
    {
        return CopyDetails::create('movie-uuid', 'The Matrix', 'DVD', 'good', 999, $stock, $active);
    }

    public function test_updates_quantity_when_purchasable(): void
    {
        $userId = Uuid::generate()->value();
        $copyId = Uuid::generate()->value();

        $cart = Cart::dddCreate(Uuid::create($userId));
        $cart->addItem(Uuid::create($copyId), Quantity::create(1));

        $cartRepository = Mockery::mock(CartRepositoryInterface::class);
        $cartRepository->shouldReceive('findByUserIdForUpdate')->once()->andReturn($cart);
        $cartRepository->shouldReceive('save')->once();

        $provider = Mockery::mock(CopyDetailsProviderInterface::class);
        $provider->shouldReceive('getByIds')->andReturn([$copyId => $this->copyDetails()]);

        $response = (new UpdateCartItemQuantity($cartRepository, $provider, $this->transactionRunner()))($userId, $copyId, 3);

        $this->assertCount(1, $response->items);
        $this->assertSame(3, $response->items[0]['quantity']);
    }

    public function test_throws_when_copy_is_unavailable(): void
    {
        $userId = Uuid::generate()->value();
        $copyId = Uuid::generate()->value();

        $cartRepository = Mockery::mock(CartRepositoryInterface::class);
        $cartRepository->shouldNotReceive('findByUserIdForUpdate');
        $cartRepository->shouldNotReceive('save');

        $provider = Mockery::mock(CopyDetailsProviderInterface::class);
        $provider->shouldReceive('getByIds')->andReturn([$copyId => $this->copyDetails(stock: 0, active: false)]);

        $this->expectException(CopyNotPurchasableException::class);

        (new UpdateCartItemQuantity($cartRepository, $provider, $this->transactionRunner()))($userId, $copyId, 1);
    }

    public function test_throws_when_quantity_exceeds_stock(): void
    {
        $userId = Uuid::generate()->value();
        $copyId = Uuid::generate()->value();

        $cartRepository = Mockery::mock(CartRepositoryInterface::class);
        $cartRepository->shouldNotReceive('save');

        $provider = Mockery::mock(CopyDetailsProviderInterface::class);
        $provider->shouldReceive('getByIds')->andReturn([$copyId => $this->copyDetails(stock: 1)]);

        $this->expectException(CopyNotPurchasableException::class);

        (new UpdateCartItemQuantity($cartRepository, $provider, $this->transactionRunner()))($userId, $copyId, 5);
    }
}
