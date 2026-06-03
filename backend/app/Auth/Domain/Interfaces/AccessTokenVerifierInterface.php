<?php

declare(strict_types=1);

namespace App\Auth\Domain\Interfaces;

use App\Auth\Domain\Exception\ExpiredAccessTokenException;
use App\Auth\Domain\Exception\InvalidAccessTokenException;
use App\Auth\Domain\ValueObject\AccessTokenPayload;

interface AccessTokenVerifierInterface
{
    /**
     * @throws InvalidAccessTokenException
     * @throws ExpiredAccessTokenException
     */
    public function verify(string $token): AccessTokenPayload;
}
