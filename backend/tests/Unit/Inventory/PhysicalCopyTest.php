<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Inventory\Domain\Entity\PhysicalCopy;
use App\Inventory\Domain\Exception\InsufficientStockException;
use App\Inventory\Domain\ValueObject\CopyCondition;
use App\Inventory\Domain\ValueObject\CopyFormat;
use App\Inventory\Domain\ValueObject\SkuCode;
use App\Inventory\Domain\ValueObject\StockQuantity;
use App\Shared\Domain\ValueObject\MoneyAmount;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class PhysicalCopyTest extends TestCase
{
    private function makeCopy(int $available = 10, int $reserved = 0): PhysicalCopy
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

    public function test_ddd_create_starts_with_zero_reserved_stock(): void
    {
        $copy = PhysicalCopy::dddCreate(
            Uuid::generate(),
            SkuCode::create('INC-BD-EU-NEW'),
            null,
            CopyFormat::create('BLURAY'),
            null,
            CopyCondition::create('new'),
            null,
            MoneyAmount::create(1500, 'EUR'),
            StockQuantity::create(10),
        );

        $this->assertSame(10, $copy->stockAvailable()->value());
        $this->assertSame(0, $copy->stockReserved()->value());
        $this->assertTrue($copy->isActive());
    }

    public function test_reserve_stock_moves_available_to_reserved(): void
    {
        $copy = $this->makeCopy(available: 10, reserved: 0);

        $copy->reserveStock(3);

        $this->assertSame(7, $copy->stockAvailable()->value());
        $this->assertSame(3, $copy->stockReserved()->value());
        $this->assertTrue($copy->wasModified());
    }

    public function test_reserve_stock_throws_when_not_enough_available(): void
    {
        $copy = $this->makeCopy(available: 2, reserved: 0);

        $this->expectException(InsufficientStockException::class);

        $copy->reserveStock(3);
    }

    public function test_reserve_stock_throws_on_non_positive_quantity(): void
    {
        $copy = $this->makeCopy();

        $this->expectException(\InvalidArgumentException::class);

        $copy->reserveStock(0);
    }

    public function test_release_reserved_stock_moves_reserved_to_available(): void
    {
        $copy = $this->makeCopy(available: 7, reserved: 3);

        $copy->releaseReservedStock(2);

        $this->assertSame(9, $copy->stockAvailable()->value());
        $this->assertSame(1, $copy->stockReserved()->value());
    }

    public function test_release_reserved_stock_throws_when_not_enough_reserved(): void
    {
        $copy = $this->makeCopy(available: 7, reserved: 1);

        $this->expectException(InsufficientStockException::class);

        $copy->releaseReservedStock(2);
    }

    public function test_confirm_stock_sale_decrements_reserved_only(): void
    {
        $copy = $this->makeCopy(available: 7, reserved: 3);

        $copy->confirmStockSale(2);

        $this->assertSame(7, $copy->stockAvailable()->value());
        $this->assertSame(1, $copy->stockReserved()->value());
    }

    public function test_confirm_stock_sale_throws_when_not_enough_reserved(): void
    {
        $copy = $this->makeCopy(available: 7, reserved: 1);

        $this->expectException(InsufficientStockException::class);

        $copy->confirmStockSale(2);
    }

    public function test_restock_adds_to_available(): void
    {
        $copy = $this->makeCopy(available: 5, reserved: 0);

        $copy->restock(3);

        $this->assertSame(8, $copy->stockAvailable()->value());
        $this->assertSame(0, $copy->stockReserved()->value());
    }
}
