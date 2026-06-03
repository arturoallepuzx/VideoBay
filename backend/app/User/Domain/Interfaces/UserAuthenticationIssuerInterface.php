<?php

declare(strict_types=1);

namespace App\User\Domain\Interfaces;

use App\User\Domain\ValueObject\AuthenticationSubject;
use App\User\Domain\ValueObject\IssuedAuthentication;

interface UserAuthenticationIssuerInterface
{
    public function issueFor(AuthenticationSubject $subject): IssuedAuthentication;
}
