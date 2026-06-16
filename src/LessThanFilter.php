<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements with numeric value less than the threshold.
 */
final class LessThanFilter extends AbstractFilter
{
    public function __construct(
        private readonly float $threshold,
        iterable $iterable = []
    ) {
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
        return is_numeric($value) && (float) $value < $this->threshold;
    }
}
