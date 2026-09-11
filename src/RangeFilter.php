<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements with numeric value within an inclusive range [min, max].
 */
final class RangeFilter extends AbstractFilter
{
    public function __construct(
        private readonly int|float|string $min,
        private readonly int|float|string $max,
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
        return new self($min, $this->max, $this->iterable);
    }

    public function withMax(int|float|string $max): static
    {
        return new self($this->min, $max, $this->iterable);
    }

    public function getMin(): int|float|string
    {
        return $this->min;
    }

    public function getMax(): int|float|string
    {
        return $this->max;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        $minComparison = NumericValueComparator::compare($value, $this->min);
        $maxComparison = NumericValueComparator::compare($value, $this->max);

        return $minComparison !== null
            && $maxComparison !== null
            && $minComparison >= 0
            && $maxComparison <= 0;
    }
}
