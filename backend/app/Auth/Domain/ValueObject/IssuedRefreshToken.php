<?php

declare(strict_types=1);

namespace App\Auth\Domain\ValueObject;

use App\Auth\Domain\Entity\RefreshToken;

class IssuedRefreshToken
{
    private RefreshToken $entity;

    private RefreshTokenSecret $secret;

    private function __construct(RefreshToken $entity, RefreshTokenSecret $secret)
    {
        $this->entity = $entity;
        $this->secret = $secret;
    }

    public static function create(RefreshToken $entity, RefreshTokenSecret $secret): self
    {
        return new self($entity, $secret);
    }

    public function entity(): RefreshToken
    {
        return $this->entity;
    }

    public function secret(): RefreshTokenSecret
    {
        return $this->secret;
    }
}
