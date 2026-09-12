<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements with numeric value between min and max.
 */
final class BetweenFilter extends AbstractFilter
{
    public function __construct(
        private readonly int|float|string $min,
        private readonly int|float|string $max,
        private readonly bool $inclusive = true,
        iterable $iterable = []
    ) {
        $comparison = NumericValueComparator::compare($min, $max);

        if ($comparison === null || $comparison > 0) {
            throw new \InvalidArgumentException('Bounds must be valid numeric values and min must not exceed max');
        }

        parent::__construct($iterable);
    }

    public function withMin(int|float|string $min): static
    {
        return new self($min, $this->max, $this->inclusive, $this->iterable);
    }

    public function withMax(int|float|string $max): static
    {
        return new self($this->min, $max, $this->inclusive, $this->iterable);
    }

    public function withInclusive(bool $inclusive): static
    {
        return new self($this->min, $this->max, $inclusive, $this->iterable);
    }

    public function getMin(): int|float|string
    {
        return $this->min;
    }

    public function getMax(): int|float|string
    {
        return $this->max;
    }

    public function isInclusive(): bool
    {
        return $this->inclusive;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        $minComparison = NumericValueComparator::compare($value, $this->min);

        if ($minComparison === null) {
            return false;
        }

        $maxComparison = NumericValueComparator::compare($value, $this->max);

        if ($this->inclusive) {
            return $minComparison >= 0 && $maxComparison <= 0;
        }

        return $minComparison > 0 && $maxComparison < 0;
    }
}
