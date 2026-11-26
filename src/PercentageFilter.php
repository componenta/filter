<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Keeps a percentage of elements from the beginning of an iterable.
 *
 * This is a collection operator, not a predicate: acceptance depends on the
 * size and position of the complete iterable.
 */
final class PercentageFilter extends AbstractCollectionFilter
{
    public function __construct(
        private readonly float $percentage,
        iterable $iterable = []
    ) {
        if (!is_finite($percentage) || $percentage < 0.0 || $percentage > 100.0) {
            throw new \InvalidArgumentException('Percentage must be a finite number between 0 and 100');
        }

        parent::__construct($iterable);
    }

    public function withPercentage(float $percentage): static
    {
        return new self($percentage, $this->iterable);
    }

    public function getPercentage(): float
    {
        return $this->percentage;
    }

    public function getIterator(): \Generator
    {
        if ($this->percentage === 0.0) {
            return;
        }

        if ($this->percentage === 100.0) {
            yield from $this->iterable;

            return;
        }

        if (is_array($this->iterable) || $this->iterable instanceof \Countable) {
            $count = count($this->iterable);

            if ($count < 0) {
                throw new \UnexpectedValueException('A countable source cannot have a negative size');
            }

            $allowedCount = self::calculateCount($count, $this->percentage);

            if ($allowedCount === 0) {
                return;
            }

            $yielded = 0;

            foreach ($this->iterable as $key => $value) {
                yield $key => $value;

                if (++$yielded >= $allowedCount) {
                    break;
                }
            }

            return;
        }

        $items = [];

        foreach ($this->iterable as $key => $value) {
            $items[] = [$key, $value];
        }

        $allowedCount = self::calculateCount(count($items), $this->percentage);

        for ($i = 0; $i < $allowedCount; $i++) {
            [$key, $value] = $items[$i];
            yield $key => $value;
        }
    }

    /**
     * Floors count * percentage / 100 using the shortest decimal representation
     * of the finite float, without rounding a near-boundary value up or down.
     * The caller guarantees 0 <= percentage <= 100 and a non-negative count.
     */
    private static function calculateCount(int $count, float $percentage): int
    {
        if ($count === 0 || $percentage === 0.0) {
            return 0;
        }

        if ($percentage === floor($percentage)) {
            $integer = (int) $percentage;

            return intdiv($count, 100) * $integer
                + intdiv(($count % 100) * $integer, 100);
        }

        [$digits, $decimalPlaces] = self::decimalRatio($percentage);

        // Prefer integer arithmetic when both coefficients fit and no
        // multiplication can overflow, even for a synthetic large Countable.
        $maxDigits = strlen((string) PHP_INT_MAX) - 1;

        if ($decimalPlaces <= $maxDigits && strlen($digits) <= $maxDigits) {
            $numerator = (int) $digits;
            $denominator = (int) str_pad('1', $decimalPlaces + 1, '0');

            if ($count <= intdiv(PHP_INT_MAX, $numerator)) {
                return intdiv($count * $numerator, $denominator);
            }

            $remainder = $count % $denominator;

            if ($remainder <= intdiv(PHP_INT_MAX, $numerator)) {
                return intdiv($count, $denominator) * $numerator
                    + intdiv($remainder * $numerator, $denominator);
            }
        }

        $left = (string) $count;
        $leftLength = strlen($left);
        $rightLength = strlen($digits);

        if ($leftLength + $rightLength <= $decimalPlaces) {
            return 0;
        }

        $product = array_fill(0, $leftLength + $rightLength, 0);

        for ($i = $leftLength - 1; $i >= 0; $i--) {
            for ($j = $rightLength - 1; $j >= 0; $j--) {
                $product[$i + $j + 1] += (ord($left[$i]) - 48) * (ord($digits[$j]) - 48);
            }
        }

        for ($i = count($product) - 1; $i > 0; $i--) {
            $product[$i - 1] += intdiv($product[$i], 10);
            $product[$i] %= 10;
        }

        $number = ltrim(implode('', $product), '0');
        $length = strlen($number) - $decimalPlaces;

        if ($length <= 0 || $number === '') {
            return 0;
        }

        return (int) substr($number, 0, $length);
    }

    /**
     * @return array{string, int} Decimal digits and denominator power of ten
     * including the division by one hundred.
     */
    private static function decimalRatio(float $percentage): array
    {
        // At 0 < percentage < 100, decimals with at most four fractional
        // digits are separated by at least 1e-4, vastly more than a float ULP.
        // An exact float round trip identifies the unique shortest decimal
        // at that scale; values one ULP away cannot take this path.
        $scale = 10;

        for ($places = 1; $places <= 4; $places++, $scale *= 10) {
            $numerator = (int) round($percentage * $scale);

            if ($numerator / $scale === $percentage) {
                return [(string) $numerator, $places + 2];
            }
        }

        // The remaining values use PHP's shortest round-trip decimal form.
        [$mantissa, $exponent] = explode('e', strtolower(FloatDecimalString::from($percentage)), 2) + [1 => '0'];
        $dot = strpos($mantissa, '.');
        $decimalPlaces = 2 + ($dot === false ? 0 : strlen($mantissa) - $dot - 1) - (int) $exponent;
        $digits = ltrim(str_replace('.', '', $mantissa), '0');
        $stripped = rtrim($digits, '0');
        $decimalPlaces -= strlen($digits) - strlen($stripped);

        return [$stripped, $decimalPlaces];
    }
}
