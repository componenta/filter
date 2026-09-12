<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose numeric value is a multiple of the divisor.
 *
 * Decimal values are checked exactly first. When an actual PHP float is
 * involved and the exact decimal representation is not divisible, a bounded
 * tolerance is used only to account for binary floating-point noise.
 */
final class MultipleOfFilter extends AbstractFilter
{
    public function __construct(
        private readonly int|float|string $divisor,
        iterable $iterable = []
    ) {
        $zeroComparison = NumericValueComparator::compare($divisor, 0);

        if ($zeroComparison === null || $zeroComparison === 0) {
            throw new \InvalidArgumentException('Divisor must be a valid non-zero numeric value');
        }

        parent::__construct($iterable);
    }

    public function withDivisor(int|float|string $divisor): static
    {
        return new self($divisor, $this->iterable);
    }

    public function getDivisor(): int|float|string
    {
        return $this->divisor;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        $exact = NumericValueComparator::isMultipleOf($value, $this->divisor);

        if ($exact === null) {
            return false;
        }

        if ($exact || !is_float($value)) {
            return $exact;
        }

        return $this->acceptFloat($value);
    }

    private function acceptFloat(float $value): bool
    {
        $divisor = (float) $this->divisor;

        if (!is_finite($divisor) || $divisor == 0.0) {
            return false;
        }

        $quotient = $value / $divisor;

        if (!is_finite($quotient)) {
            return false;
        }

        if (abs($quotient) > 9_007_199_254_740_992.0) {
            return false;
        }

        $nearestInteger = round($quotient);

        if ($nearestInteger == 0.0) {
            return false;
        }

        if (($nearestInteger * $divisor) === $value) {
            return true;
        }

        $scaledTolerance = PHP_FLOAT_EPSILON * max(1.0, abs($quotient)) * 8;
        $tolerance = min(1.0e-9, $scaledTolerance);

        return abs($quotient - $nearestInteger) <= $tolerance;
    }
}
