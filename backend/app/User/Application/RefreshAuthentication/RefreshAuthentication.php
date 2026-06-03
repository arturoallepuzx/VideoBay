<?php

declare(strict_types=1);

namespace App\User\Application\RefreshAuthentication;

use App\User\Domain\Interfaces\UserAuthenticationRefresherInterface;

class RefreshAuthentication
{
    public function __construct(
        private UserAuthenticationRefresherInterface $userAuthenticationRefresher,
    ) {}

    public function __invoke(string $refreshCredential): RefreshAuthenticationResponse
    {
        $issuedAuthentication = $this->userAuthenticationRefresher->refresh($refreshCredential);

        return RefreshAuthenticationResponse::create($issuedAuthentication);
    }
}
