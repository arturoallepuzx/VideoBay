<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Streaming\Domain\ValueObject\StoragePrice;
use Tests\TestCase;

class StoragePriceTest extends TestCase
{
    public function test_charges_a_single_video_without_the_free_allowance(): void
    {
        $price = StoragePrice::create(0.015, 10);

        $this->assertEqualsWithDelta(0.03, $price->monthlyCostFor(2000000000), 0.000001);
    }

    public function test_subtracts_the_free_allowance_from_the_account_total(): void
    {
        $price = StoragePrice::create(0.015, 10);

        $this->assertEqualsWithDelta(1.5, $price->monthlyCostForAccount(110000000000), 0.000001);
    }

    public function test_the_account_is_free_while_it_stays_under_the_allowance(): void
    {
        $price = StoragePrice::create(0.015, 10);

        $this->assertSame(0.0, $price->monthlyCostForAccount(9000000000));
        $this->assertSame(0.0, $price->monthlyCostForAccount(0));
    }

    public function test_counts_gigabytes_in_decimal_like_the_provider_does(): void
    {
        $price = StoragePrice::create(1.0, 0);

        $this->assertEqualsWithDelta(1.0, $price->monthlyCostFor(1000000000), 0.000001);
    }

    public function test_rejects_negative_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        StoragePrice::create(-0.01, 10);
    }
}
