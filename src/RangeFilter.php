<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements with numeric value within an inclusive range [min, max].
 */
final class RangeFilter extends AbstractFilter
{
    public function __construct(
        private readonly float $min,
        private readonly float $max,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withMin(float $min): static
    {
        return new self($min, $this->max, $this->iterable);
    }

    public function withMax(float $max): static
    {
        return new self($this->min, $max, $this->iterable);
    }

    public function getMin(): float
    {
        return $this->min;
    }

    public function getMax(): float
    {
        return $this->max;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $num = (float) $value;
        return $num >= $this->min && $num <= $this->max;
    }
}
