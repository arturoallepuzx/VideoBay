<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Inventory\Application\ConfirmStockSale\ConfirmStockSale;
use App\Inventory\Domain\Entity\PhysicalCopy;
use App\Inventory\Domain\Exception\InsufficientStockException;
use App\Inventory\Domain\Exception\PhysicalCopyNotFoundException;
use App\Inventory\Domain\Interfaces\PhysicalCopyRepositoryInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Closure;
use Mockery;
use Tests\TestCase;

class ConfirmStockSaleTest extends TestCase
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

    private function copy(int $available, int $reserved): PhysicalCopy
    {
        return PhysicalCopy::fromPersistence(
            Uuid::generate()->value(),
            Uuid::generate()->value(),
            'INC-BD-EU-NEW',
            null,
            'BLURAY',
            null,
            'new',
            null,
            1500,
            'EUR',
            $available,
            $reserved,
            true,
            new \DateTimeImmutable,
            new \DateTimeImmutable,
        );
    }

    public function test_confirm_decrements_reserved_only(): void
    {
        $copy = $this->copy(available: 7, reserved: 3);

        $repository = Mockery::mock(PhysicalCopyRepositoryInterface::class);
        $repository->shouldReceive('findByUuidForUpdate')->once()->andReturn($copy);
        $repository->shouldReceive('update')->once()->with($copy);

        (new ConfirmStockSale($repository, $this->transactionRunner()))($copy->id()->value(), 2);

        $this->assertSame(7, $copy->stockAvailable()->value());
        $this->assertSame(1, $copy->stockReserved()->value());
    }

    public function test_throws_when_copy_not_found(): void
    {
        $repository = Mockery::mock(PhysicalCopyRepositoryInterface::class);
        $repository->shouldReceive('findByUuidForUpdate')->once()->andReturn(null);
        $repository->shouldNotReceive('update');

        $this->expectException(PhysicalCopyNotFoundException::class);

        (new ConfirmStockSale($repository, $this->transactionRunner()))(Uuid::generate()->value(), 1);
    }

    public function test_throws_when_not_enough_reserved(): void
    {
        $copy = $this->copy(available: 7, reserved: 1);

        $repository = Mockery::mock(PhysicalCopyRepositoryInterface::class);
        $repository->shouldReceive('findByUuidForUpdate')->once()->andReturn($copy);
        $repository->shouldNotReceive('update');

        $this->expectException(InsufficientStockException::class);

        (new ConfirmStockSale($repository, $this->transactionRunner()))($copy->id()->value(), 2);
    }
}
