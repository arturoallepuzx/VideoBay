<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Inventory\Application\ReserveStock\ReserveStock;
use App\Inventory\Domain\Entity\PhysicalCopy;
use App\Inventory\Domain\Exception\InsufficientStockException;
use App\Inventory\Domain\Exception\PhysicalCopyNotFoundException;
use App\Inventory\Domain\Interfaces\PhysicalCopyRepositoryInterface;
use App\Shared\Domain\Interfaces\TransactionRunnerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Closure;
use Mockery;
use Tests\TestCase;

class ReserveStockTest extends TestCase
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

    private function copy(int $available, int $reserved = 0): PhysicalCopy
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

    public function test_reserves_stock_and_persists(): void
    {
        $copy = $this->copy(available: 10);

        $repository = Mockery::mock(PhysicalCopyRepositoryInterface::class);
        $repository->shouldReceive('findByUuidForUpdate')->once()->andReturn($copy);
        $repository->shouldReceive('update')->once()->with($copy);

        (new ReserveStock($repository, $this->transactionRunner()))($copy->id()->value(), 3);

        $this->assertSame(7, $copy->stockAvailable()->value());
        $this->assertSame(3, $copy->stockReserved()->value());
    }

    public function test_throws_when_copy_not_found(): void
    {
        $repository = Mockery::mock(PhysicalCopyRepositoryInterface::class);
        $repository->shouldReceive('findByUuidForUpdate')->once()->andReturn(null);
        $repository->shouldNotReceive('update');

        $this->expectException(PhysicalCopyNotFoundException::class);

        (new ReserveStock($repository, $this->transactionRunner()))(Uuid::generate()->value(), 1);
    }

    public function test_throws_when_insufficient_stock(): void
    {
        $copy = $this->copy(available: 2);

        $repository = Mockery::mock(PhysicalCopyRepositoryInterface::class);
        $repository->shouldReceive('findByUuidForUpdate')->once()->andReturn($copy);
        $repository->shouldNotReceive('update');

        $this->expectException(InsufficientStockException::class);

        (new ReserveStock($repository, $this->transactionRunner()))($copy->id()->value(), 5);
    }
}
