<?php

declare(strict_types=1);

namespace App\Streaming\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;

interface VideoFileRegistrationJobDispatcherInterface
{
    public function dispatch(Uuid $videoFileUuid): void;
}
