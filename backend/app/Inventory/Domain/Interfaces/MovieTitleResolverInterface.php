<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;

interface MovieTitleResolverInterface
{
    /**
     * @param  list<Uuid>  $movieIds
     * @return array<string, string> uuid string => title
     */
    public function resolveTitles(array $movieIds): array;
}
