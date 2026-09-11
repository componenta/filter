<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements with numeric value greater than the threshold.
 */
final class GreaterThanFilter extends AbstractFilter
{
    public function __construct(
        private readonly float $threshold,
        iterable $iterable = []
    ) {
        if (!is_finite($threshold)) {
            throw new \InvalidArgumentException('Threshold must be finite');
        }

        parent::__construct($iterable);
    }

    public function withThreshold(float $threshold): static
    {
        return new self($threshold, $this->iterable);
    }

    public function getThreshold(): float
    {
        return $this->threshold;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $number = (float) $value;

        return is_finite($number) && $number > $this->threshold;
    }
}
