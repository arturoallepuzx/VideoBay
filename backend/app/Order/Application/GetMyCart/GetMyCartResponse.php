<?php

declare(strict_types=1);

namespace App\Order\Application\GetMyCart;

use App\Order\Domain\Entity\Cart;
use App\Order\Domain\ValueObject\CopyDetails;

final readonly class GetMyCartResponse
{
    /** @param list<array<string, mixed>> $items */
    private function __construct(
        public array $items,
        public int $totalCents,
        public bool $hasUnavailableItems,
    ) {}

    public static function empty(): self
    {
        return new self([], 0, false);
    }

    /**
     * @param  array<string, CopyDetails>  $details  keyed por copy uuid
     */
    public static function fromCart(Cart $cart, array $details): self
    {
        $items = [];
        $totalCents = 0;
        $hasUnavailable = false;

        foreach ($cart->items() as $item) {
            $copyId = $item->physicalCopyId()->value();
            $quantity = $item->quantity()->value();
            $detail = $details[$copyId] ?? null;

            $available = $detail !== null && $detail->canFulfill($quantity);
            $subtotal = $available ? $detail->priceCents() * $quantity : 0;

            if ($available) {
                $totalCents += $subtotal;
            } else {
                $hasUnavailable = true;
            }

            $items[] = [
                'physical_copy_id' => $copyId,
                'quantity' => $quantity,
                'available' => $available,
                'movie_id' => $detail?->movieId(),
                'movie_title' => $detail?->movieTitle(),
                'poster_path' => $detail?->posterPath(),
                'format' => $detail?->format(),
                'condition' => $detail?->condition(),
                'unit_price_cents' => $detail?->priceCents(),
                'stock_available' => $detail?->stockAvailable(),
                'subtotal_cents' => $subtotal,
            ];
        }

        return new self($items, $totalCents, $hasUnavailable);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'total_cents' => $this->totalCents,
            'has_unavailable_items' => $this->hasUnavailableItems,
        ];
    }
}
