<?php

namespace App\User\Domain\Interfaces;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;
}
