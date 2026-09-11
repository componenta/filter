<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements with numeric value less than the threshold.
 */
final class LessThanFilter extends AbstractFilter
{
    public function __construct(
        private readonly int|float|string $threshold,
        iterable $iterable = []
    ) {
        if (!NumericValueComparator::isValid($threshold)) {
            throw new \InvalidArgumentException('Threshold must be a valid numeric value');
        }

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
        $comparison = NumericValueComparator::compare($value, $this->threshold);

        return $comparison !== null && $comparison < 0;
    }
}
