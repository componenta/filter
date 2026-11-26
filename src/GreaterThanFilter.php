<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements with numeric value greater than the threshold.
 */
final class GreaterThanFilter extends AbstractFilter
{
    private readonly array $normalizedThreshold;

    public function __construct(
        private readonly int|float|string $threshold,
        iterable $iterable = []
    ) {
        $normalized = NumericValueComparator::normalize($threshold);

        if ($normalized === null) {
            throw new \InvalidArgumentException('Threshold must be a valid numeric value');
        }

        $this->normalizedThreshold = $normalized;
        parent::__construct($iterable);
    }

    public function withThreshold(int|float|string $threshold): static
    {
        return new self($threshold, $this->iterable);
    }

    public function getThreshold(): int|float|string
    {
        return $this->threshold;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (is_int($value) && is_int($this->threshold)) {
            return $value > $this->threshold;
        }

        $normalized = NumericValueComparator::normalize($value);

        return $normalized !== null
            && NumericValueComparator::compareNormalized($normalized, $this->normalizedThreshold) > 0;
    }
}
