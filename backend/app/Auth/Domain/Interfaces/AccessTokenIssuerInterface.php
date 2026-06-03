<?php

declare(strict_types=1);

namespace App\Auth\Domain\Interfaces;

use App\Auth\Domain\ValueObject\AccessToken;
use App\Auth\Domain\ValueObject\AccessTokenPayload;

interface AccessTokenIssuerInterface
{
    public function issue(AccessTokenPayload $payload): AccessToken;
}
