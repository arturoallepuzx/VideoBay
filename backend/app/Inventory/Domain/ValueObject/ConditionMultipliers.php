<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObject;

class ConditionMultipliers
{
    /** @var array<string, float> */
    private array $multipliers;

    /** @param array<string, float> $multipliers */
    private function __construct(array $multipliers)
    {
        foreach ($multipliers as $condition => $factor) {
            if (! in_array($condition, CopyCondition::allowed(), true)) {
                throw new \InvalidArgumentException(
                    sprintf('Unknown condition "%s" in condition multipliers.', $condition)
                );
            }

            if (! is_float($factor) && ! is_int($factor)) {
                throw new \InvalidArgumentException(
                    sprintf('Multiplier for "%s" must be numeric, got "%s".', $condition, var_export($factor, true))
                );
            }

            if ($factor < 0) {
                throw new \InvalidArgumentException(
                    sprintf('Multiplier for "%s" cannot be negative, got %f.', $condition, $factor)
                );
            }
        }

        $this->multipliers = array_map(static fn ($f): float => (float) $f, $multipliers);
    }

    /** @param array<string, float|int> $multipliers */
    public static function create(array $multipliers): self
    {
        return new self($multipliers);
    }

    public function forCondition(CopyCondition $condition): float
    {
        return $this->multipliers[$condition->value()] ?? 0.0;
    }

    /** @return array<string, float> */
    public function toArray(): array
    {
        return $this->multipliers;
    }

    public function equals(self $other): bool
    {
        return $this->multipliers === $other->multipliers;
    }
}
