<?php

declare(strict_types=1);

namespace App\User\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;

interface UserAuthenticationGlobalRevokerInterface
{
    public function revokeAllByUserId(Uuid $userId): void;
}
