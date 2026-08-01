<?php

declare(strict_types=1);

namespace Tests\Unit\Streaming;

use App\Streaming\Application\GetStorageUsage\GetStorageUsage;
use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\ValueObject\DiskUsage;
use App\Streaming\Domain\ValueObject\StorageDisk;
use App\Streaming\Domain\ValueObject\StoragePrice;
use Mockery;
use Tests\TestCase;

class GetStorageUsageTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sums_each_disk_and_prices_only_the_remote_one(): void
    {
        $repository = $this->repositoryReturning([
            DiskUsage::create(StorageDisk::r2(), 143, 527660000000),
            DiskUsage::create(StorageDisk::local(), 2, 4000000000),
        ]);

        $response = (new GetStorageUsage($repository, StoragePrice::create(0.015, 10)))()->toArray();

        $this->assertSame(531660000000, $response['total_bytes']);
        $this->assertSame(145, $response['total_video_count']);

        $this->assertSame('r2', $response['disks'][0]['disk']);
        $this->assertEqualsWithDelta(7.7649, $response['disks'][0]['monthly_cost'], 0.0001);

        $this->assertSame('local', $response['disks'][1]['disk']);
        $this->assertNull($response['disks'][1]['monthly_cost']);
    }

    public function test_hides_every_cost_when_no_price_is_configured(): void
    {
        $repository = $this->repositoryReturning([
            DiskUsage::create(StorageDisk::r2(), 143, 527660000000),
        ]);

        $response = (new GetStorageUsage($repository, null))()->toArray();

        $this->assertNull($response['disks'][0]['monthly_cost']);
        $this->assertSame(527660000000, $response['total_bytes']);
    }

    /** @param list<DiskUsage> $usages */
    private function repositoryReturning(array $usages): VideoFileRepositoryInterface
    {
        $repository = Mockery::mock(VideoFileRepositoryInterface::class);
        $repository->shouldReceive('sumUsageByDisk')->once()->andReturn($usages);

        return $repository;
    }
}
