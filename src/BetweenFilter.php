<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements with numeric value between min and max.
 */
final class BetweenFilter extends AbstractFilter
{
    public function __construct(
        private readonly float $min,
        private readonly float $max,
        private readonly bool $inclusive = true,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withMin(float $min): static
    {
        return new self($min, $this->max, $this->inclusive, $this->iterable);
    }

    public function withMax(float $max): static
    {
        return new self($this->min, $max, $this->inclusive, $this->iterable);
    }

    public function withInclusive(bool $inclusive): static
    {
        return new self($this->min, $this->max, $inclusive, $this->iterable);
    }

    public function getMin(): float
    {
        return $this->min;
    }

    public function getMax(): float
    {
        return $this->max;
    }

    public function isInclusive(): bool
    {
        return $this->inclusive;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $num = (float) $value;

        if ($this->inclusive) {
            return $num >= $this->min && $num <= $this->max;
        }

        return $num > $this->min && $num < $this->max;
    }
}
