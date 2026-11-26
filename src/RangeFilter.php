<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements with numeric value within an inclusive range [min, max].
 */
final class RangeFilter extends AbstractFilter
{
    private readonly array $normalizedMin;
    private readonly array $normalizedMax;

    public function __construct(
        private readonly int|float|string $min,
        private readonly int|float|string $max,
        iterable $iterable = []
    ) {
        $normalizedMin = NumericValueComparator::normalize($min);
        $normalizedMax = NumericValueComparator::normalize($max);

        if ($normalizedMin === null || $normalizedMax === null
            || NumericValueComparator::compareNormalized($normalizedMin, $normalizedMax) > 0
        ) {
            throw new \InvalidArgumentException('Bounds must be valid numeric values and min must not exceed max');
        }

        $this->normalizedMin = $normalizedMin;
        $this->normalizedMax = $normalizedMax;
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
        if (is_int($value) && is_int($this->min) && is_int($this->max)) {
            return $value >= $this->min && $value <= $this->max;
        }

        $normalized = NumericValueComparator::normalize($value);

        if ($normalized === null) {
            return false;
        }

        $minComparison = NumericValueComparator::compareNormalized($normalized, $this->normalizedMin);
        $maxComparison = NumericValueComparator::compareNormalized($normalized, $this->normalizedMax);

        return $minComparison >= 0 && $maxComparison <= 0;
    }
}
