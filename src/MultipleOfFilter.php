<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose numeric value is a multiple of the divisor.
 */
final class MultipleOfFilter extends AbstractFilter
{
    public function __construct(
        private readonly float $divisor,
        iterable $iterable = []
    ) {
        if (!is_finite($divisor) || $divisor == 0.0) {
            throw new \InvalidArgumentException('Divisor must be a finite non-zero number');
        }

        parent::__construct($iterable);
    }

    public function withDivisor(float $divisor): static
    {
        return new self($divisor, $this->iterable);
    }

    public function getDivisor(): float
    {
        return $this->divisor;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $num = (float) $value;

        if (!is_finite($num)) {
            return false;
        }

        $quotient = $num / $this->divisor;

        if (!is_finite($quotient)) {
            return false;
        }

        $nearestInteger = round($quotient);
        $tolerance = PHP_FLOAT_EPSILON * max(1.0, abs($quotient)) * 8;

        return abs($quotient - $nearestInteger) <= $tolerance;
    }
}
