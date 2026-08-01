<?php

declare(strict_types=1);

namespace App\Streaming\Application\GetStorageUsage;

use App\Streaming\Domain\Interfaces\VideoFileRepositoryInterface;
use App\Streaming\Domain\ValueObject\StoragePrice;

class GetStorageUsage
{
    public function __construct(
        private VideoFileRepositoryInterface $videoFileRepository,
        private ?StoragePrice $storagePrice,
    ) {}

    public function __invoke(): GetStorageUsageResponse
    {
        return GetStorageUsageResponse::create(
            $this->videoFileRepository->sumUsageByDisk(),
            $this->storagePrice,
        );
    }
}
