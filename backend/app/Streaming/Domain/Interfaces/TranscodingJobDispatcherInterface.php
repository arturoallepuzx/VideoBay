<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;

interface TranscodingJobDispatcherInterface
{
    public function dispatch(Uuid $videoFileUuid): void;
}
