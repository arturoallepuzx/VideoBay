<?php

declare(strict_types=1);

namespace App\Inventory\Application\EstimateCopyPrice;

use App\Inventory\Domain\ValueObject\CopyCondition;
use App\Inventory\Domain\ValueObject\CopyFormat;
use App\Shared\Domain\ValueObject\MoneyAmount;

final readonly class EstimateCopyPriceResponse
{
    private function __construct(
        public string $format,
        public string $condition,
        public int $sellPriceCents,
        public int $buyPriceCents,
        public string $currency,
    ) {}

    public static function create(
        CopyFormat $format,
        CopyCondition $condition,
        MoneyAmount $sellPrice,
        MoneyAmount $buyPrice,
    ): self {
        return new self(
            format: $format->value(),
            condition: $condition->value(),
            sellPriceCents: $sellPrice->cents(),
            buyPriceCents: $buyPrice->cents(),
            currency: $sellPrice->currency(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'format' => $this->format,
            'condition' => $this->condition,
            'sell_price_cents' => $this->sellPriceCents,
            'buy_price_cents' => $this->buyPriceCents,
            'currency' => $this->currency,
        ];
    }
}
