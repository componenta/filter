<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose numeric value is a multiple of the divisor.
 *
 * Integer and numeric-string inputs are compared exactly. When either operand
 * is a PHP float, a small tolerance is used to account for binary floating-point
 * representation noise already present in the input value.
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
        if (is_float($value) || is_float($this->divisor)) {
            return $this->acceptFloat($value);
        }

        return NumericValueComparator::isMultipleOf($value, $this->divisor) ?? false;
    }

    private function acceptFloat(mixed $value): bool
    {
        if (!is_int($value) && !is_float($value) && !is_string($value)) {
            return false;
        }

        if (!NumericValueComparator::isValid($value)) {
            return false;
        }

        $number = (float) $value;
        $divisor = (float) $this->divisor;

        if (!is_finite($number) || !is_finite($divisor) || $divisor == 0.0) {
            return false;
        }

        $quotient = $number / $divisor;

        if (!is_finite($quotient)) {
            return false;
        }

        $nearestInteger = round($quotient);
        $scaledTolerance = PHP_FLOAT_EPSILON * max(1.0, abs($quotient)) * 8;
        $tolerance = min(1.0e-9, $scaledTolerance);

        return abs($quotient - $nearestInteger) <= $tolerance;
    }
}
