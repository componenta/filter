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

        if ($exact || (!is_float($value) && !is_float($this->divisor))) {
            return $exact;
        }

        return $this->acceptFloat($value);
    }

    private function acceptFloat(mixed $value): bool
    {
        $number = (float) $value;
        $divisor = (float) $this->divisor;

        if (!is_finite($number) || !is_finite($divisor) || $divisor == 0.0) {
            return false;
        }

        $quotient = $number / $divisor;

        if (!is_finite($quotient)) {
            return false;
        }

        if ($quotient == 0.0 && NumericValueComparator::compare($value, 0) !== 0) {
            return false;
        }

        $nearestInteger = round($quotient);
        $scaledTolerance = PHP_FLOAT_EPSILON * max(1.0, abs($quotient)) * 8;
        $tolerance = min(1.0e-9, $scaledTolerance);

        return abs($quotient - $nearestInteger) <= $tolerance;
    }
}
