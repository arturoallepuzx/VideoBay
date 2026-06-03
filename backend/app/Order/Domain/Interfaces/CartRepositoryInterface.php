<?php

declare(strict_types=1);

namespace App\Order\Domain\Interfaces;

use App\Order\Domain\Entity\Cart;
use App\Shared\Domain\ValueObject\Uuid;

interface CartRepositoryInterface
{
    public function findByUserId(Uuid $userId): ?Cart;

    public function findByUserIdForUpdate(Uuid $userId): ?Cart;

    public function save(Cart $cart): void;

    public function delete(Cart $cart): void;
}
