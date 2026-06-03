<?php

declare(strict_types=1);

namespace App\Order\Application\GetOrderStatusCounts;

use App\Order\Domain\ValueObject\OrderStatus;

final readonly class GetOrderStatusCountsResponse
{
    /** @param array<string, int> $counts */
    private function __construct(
        public array $counts,
    ) {}

    /**
     * @param  array<string, int>  $counts
     */
    public static function create(array $counts): self
    {
        $normalized = [];

        foreach (OrderStatus::all() as $status) {
            $normalized[$status] = $counts[$status] ?? 0;
        }

        return new self($normalized);
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return $this->counts;
    }
}
