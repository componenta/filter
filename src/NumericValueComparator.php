<?php

declare(strict_types=1);

namespace Componenta\Filter;

/** @internal */
final class NumericValueComparator
{
    private const string PATTERN = '/^[+-]?(?:(\d+)(?:\.(\d*))?|\.(\d+))(?:[eE]([+-]?\d+))?$/D';

    private function __construct()
    {
    }

    /**
     * @return -1|0|1|null Null when either value is not a supported finite numeric value.
     */
    public static function compare(mixed $left, mixed $right): ?int
    {
        $left = self::parse($left);
        $right = self::parse($right);

        if ($left === null || $right === null) {
            return null;
        }

        [$leftSign, $leftDigits, $leftOrder] = $left;
        [$rightSign, $rightDigits, $rightOrder] = $right;

        if ($leftSign !== $rightSign) {
            return $leftSign <=> $rightSign;
        }

        if ($leftSign === 0) {
            return 0;
        }

        $orderComparison = self::compareSignedInteger($leftOrder, $rightOrder);

        if ($orderComparison !== 0) {
            return $leftSign > 0 ? $orderComparison : -$orderComparison;
        }

        $digitComparison = self::compareSignificands($leftDigits, $rightDigits);

        return $leftSign > 0 ? $digitComparison : -$digitComparison;
    }

    /**
     * Checks mathematically exact divisibility for decimal values.
     *
     * @return bool|null Null when either value is invalid or the divisor is zero.
     */
    public static function isMultipleOf(mixed $value, mixed $divisor): ?bool
    {
        $value = self::parse($value);
        $divisor = self::parse($divisor);

        if ($value === null || $divisor === null || $divisor[0] === 0) {
            return null;
        }

        if ($value[0] === 0) {
            return true;
        }

        [, $valueDigits, , $valueScale] = $value;
        [, $divisorDigits, , $divisorScale] = $divisor;

        [$valueDigits, $valueScale] = self::stripTrailingZeros($valueDigits, $valueScale);
        [$divisorDigits, $divisorScale] = self::stripTrailingZeros($divisorDigits, $divisorScale);

        [$divisorTwos, $coprimeDivisor] = self::factorOut($divisorDigits, 2);
        [$divisorFives, $coprimeDivisor] = self::factorOut($coprimeDivisor, 5);
        [$valueTwos] = self::factorOut($valueDigits, 2, $divisorTwos);
        [$valueFives] = self::factorOut($valueDigits, 5, $divisorFives);

        if (!self::isUnsignedDivisible($valueDigits, $coprimeDivisor)) {
            return false;
        }

        $requiredPowerOfTen = max(
            0,
            $divisorTwos - $valueTwos,
            $divisorFives - $valueFives,
        );

        $minimumValueScale = self::addSmallInteger($divisorScale, $requiredPowerOfTen);

        return self::compareSignedInteger($valueScale, $minimumValueScale) >= 0;
    }

    public static function isValid(mixed $value): bool
    {
        return self::parse($value) !== null;
    }

    /**
     * @return array{-1|0|1, string, array{-1|0|1, string}, array{-1|0|1, string}}|null
     */
    private static function parse(mixed $value): ?array
    {
        if (is_int($value)) {
            $number = (string) $value;
        } elseif (is_float($value)) {
            if (!is_finite($value)) {
                return null;
            }

            $number = self::floatString($value);
        } elseif (is_string($value)) {
            $number = trim($value);
        } else {
            return null;
        }

        if (preg_match(self::PATTERN, $number, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
            return null;
        }

        $sign = str_starts_with($number, '-') ? -1 : 1;

        if ($matches[1] !== null) {
            $integer = $matches[1];
            $fraction = $matches[2] ?? '';
        } else {
            $integer = '0';
            $fraction = $matches[3] ?? '';
        }

        $digits = ltrim($integer . $fraction, '0');

        if ($digits === '') {
            return [0, '0', [0, '0'], [0, '0']];
        }

        $exponent = self::parseSignedInteger($matches[4] ?? null);
        $scale = self::addSmallInteger($exponent, -strlen($fraction));
        $order = self::addSmallInteger($scale, strlen($digits));

        return [$sign, $digits, $order, $scale];
    }

    private static function floatString(float $value): string
    {
        $serializePrecision = ini_get('serialize_precision');

        if ($serializePrecision === '-1') {
            return json_encode(
                $value,
                JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
            );
        }

        $previous = ini_set('serialize_precision', '-1');

        if ($previous === false) {
            return sprintf('%.17g', $value);
        }

        try {
            return json_encode(
                $value,
                JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
            );
        } finally {
            ini_set('serialize_precision', $previous);
        }
    }

    /**
     * @param array{-1|0|1, string} $scale
     * @return array{string, array{-1|0|1, string}}
     */
    private static function stripTrailingZeros(string $digits, array $scale): array
    {
        $stripped = rtrim($digits, '0');
        $count = strlen($digits) - strlen($stripped);

        return [$stripped, self::addSmallInteger($scale, $count)];
    }

    /**
     * @return array{int<0, max>, string}
     */
    private static function factorOut(string $value, int $factor, ?int $limit = null): array
    {
        $count = 0;
        $blockPower = 1;
        $blockDivisor = $factor;
        $maximumSafeDivisor = intdiv(PHP_INT_MAX, 10);

        while ($blockDivisor <= intdiv($maximumSafeDivisor, $factor)) {
            $blockDivisor *= $factor;
            $blockPower++;
        }

        while ($value !== '0'
            && ($limit === null || $count + $blockPower <= $limit)
        ) {
            [$quotient, $remainder] = self::divideUnsignedBySmall($value, $blockDivisor);

            if ($remainder !== 0) {
                break;
            }

            $value = $quotient;
            $count += $blockPower;
        }

        while ($value !== '0' && ($limit === null || $count < $limit)) {
            [$quotient, $remainder] = self::divideUnsignedBySmall($value, $factor);

            if ($remainder !== 0) {
                break;
            }

            $value = $quotient;
            $count++;
        }

        return [$count, $value];
    }

    /** @return array{string, int} */
    private static function divideUnsignedBySmall(string $value, int $divisor): array
    {
        $remainder = 0;
        $quotient = '';

        for ($i = 0, $length = strlen($value); $i < $length; $i++) {
            $number = ($remainder * 10) + (ord($value[$i]) - 48);
            $digit = intdiv($number, $divisor);
            $remainder = $number % $divisor;

            if ($quotient !== '' || $digit !== 0) {
                $quotient .= (string) $digit;
            }
        }

        return [$quotient === '' ? '0' : $quotient, $remainder];
    }

    private static function isUnsignedDivisible(string $numerator, string $denominator): bool
    {
        if ($denominator === '1') {
            return true;
        }

        if (self::compareUnsigned($numerator, $denominator) < 0) {
            return false;
        }

        [$base, $digitsPerLimb] = PHP_INT_SIZE >= 8
            ? [1_000_000_000, 9]
            : [10_000, 4];
        $dividend = self::decimalToLimbs($numerator, $digitsPerLimb);
        $divisor = self::decimalToLimbs($denominator, $digitsPerLimb);
        $divisorLength = count($divisor);

        if ($divisorLength === 1) {
            $remainder = 0;

            foreach ($dividend as $limb) {
                $remainder = (($remainder * $base) + $limb) % $divisor[0];
            }

            return $remainder === 0;
        }

        $normalizer = intdiv($base, $divisor[0] + 1);

        if ($normalizer > 1) {
            $dividend = self::multiplyLimbsBySmall($dividend, $normalizer, $base);
            $divisor = self::multiplyLimbsBySmall($divisor, $normalizer, $base);
        }

        array_unshift($dividend, 0);
        $divisorLength = count($divisor);
        $quotientLength = count($dividend) - $divisorLength;

        for ($offset = 0; $offset < $quotientLength; $offset++) {
            $leading = ($dividend[$offset] * $base) + $dividend[$offset + 1];
            $quotient = intdiv($leading, $divisor[0]);
            $remainder = $leading % $divisor[0];

            if ($quotient >= $base) {
                $quotient = $base - 1;
                $remainder = $leading - ($quotient * $divisor[0]);
            }

            while ($quotient * $divisor[1]
                > ($remainder * $base) + $dividend[$offset + 2]
            ) {
                $quotient--;
                $remainder += $divisor[0];

                if ($remainder >= $base) {
                    break;
                }
            }

            if ($quotient === 0) {
                continue;
            }

            $product = self::multiplyLimbsBySmallFixed($divisor, $quotient, $base);

            while (self::compareLimbSegment($dividend, $offset, $product) < 0) {
                $quotient--;
                $product = self::multiplyLimbsBySmallFixed($divisor, $quotient, $base);
            }

            self::subtractLimbSegment($dividend, $offset, $product, $base);
        }

        for ($i = count($dividend) - $divisorLength; $i < count($dividend); $i++) {
            if ($dividend[$i] !== 0) {
                return false;
            }
        }

        return true;
    }

    /** @return list<int> */
    private static function decimalToLimbs(string $value, int $digitsPerLimb): array
    {
        $firstLength = strlen($value) % $digitsPerLimb;
        $offset = 0;
        $limbs = [];

        if ($firstLength !== 0) {
            $limbs[] = (int) substr($value, 0, $firstLength);
            $offset = $firstLength;
        }

        for ($length = strlen($value); $offset < $length; $offset += $digitsPerLimb) {
            $limbs[] = (int) substr($value, $offset, $digitsPerLimb);
        }

        return $limbs;
    }

    /**
     * @param list<int> $limbs
     * @return list<int>
     */
    private static function multiplyLimbsBySmall(array $limbs, int $multiplier, int $base): array
    {
        $carry = 0;

        for ($i = count($limbs) - 1; $i >= 0; $i--) {
            $product = ($limbs[$i] * $multiplier) + $carry;
            $limbs[$i] = $product % $base;
            $carry = intdiv($product, $base);
        }

        if ($carry !== 0) {
            array_unshift($limbs, $carry);
        }

        return $limbs;
    }

    /**
     * @param list<int> $limbs
     * @return list<int>
     */
    private static function multiplyLimbsBySmallFixed(array $limbs, int $multiplier, int $base): array
    {
        $result = array_fill(0, count($limbs) + 1, 0);
        $carry = 0;

        for ($i = count($limbs) - 1; $i >= 0; $i--) {
            $product = ($limbs[$i] * $multiplier) + $carry;
            $result[$i + 1] = $product % $base;
            $carry = intdiv($product, $base);
        }

        $result[0] = $carry;

        return $result;
    }

    /**
     * @param list<int> $value
     * @param list<int> $other
     * @return -1|0|1
     */
    private static function compareLimbSegment(array $value, int $offset, array $other): int
    {
        foreach ($other as $i => $limb) {
            if ($value[$offset + $i] !== $limb) {
                return $value[$offset + $i] <=> $limb;
            }
        }

        return 0;
    }

    /**
     * @param list<int> $value
     * @param list<int> $subtrahend
     */
    private static function subtractLimbSegment(array &$value, int $offset, array $subtrahend, int $base): void
    {
        $borrow = 0;

        for ($i = count($subtrahend) - 1; $i >= 0; $i--) {
            $index = $offset + $i;
            $difference = $value[$index] - $subtrahend[$i] - $borrow;

            if ($difference < 0) {
                $difference += $base;
                $borrow = 1;
            } else {
                $borrow = 0;
            }

            $value[$index] = $difference;
        }
    }

    /** @return array{-1|0|1, string} */
    private static function parseSignedInteger(?string $value): array
    {
        if ($value === null || $value === '') {
            return [0, '0'];
        }

        $negative = $value[0] === '-';

        if ($value[0] === '+' || $negative) {
            $value = substr($value, 1);
        }

        $magnitude = ltrim($value, '0');

        if ($magnitude === '') {
            return [0, '0'];
        }

        return [$negative ? -1 : 1, $magnitude];
    }

    /**
     * @param array{-1|0|1, string} $value
     * @return array{-1|0|1, string}
     */
    private static function addSmallInteger(array $value, int $delta): array
    {
        if ($delta === 0) {
            return $value;
        }

        [$sign, $magnitude] = $value;
        $deltaSign = $delta < 0 ? -1 : 1;
        $deltaMagnitude = (string) abs($delta);

        if ($sign === 0) {
            return [$deltaSign, $deltaMagnitude];
        }

        if ($sign === $deltaSign) {
            return [$sign, self::addUnsigned($magnitude, $deltaMagnitude)];
        }

        $comparison = self::compareUnsigned($magnitude, $deltaMagnitude);

        if ($comparison === 0) {
            return [0, '0'];
        }

        if ($comparison > 0) {
            return [$sign, self::subtractUnsigned($magnitude, $deltaMagnitude)];
        }

        return [$deltaSign, self::subtractUnsigned($deltaMagnitude, $magnitude)];
    }

    /**
     * @param array{-1|0|1, string} $left
     * @param array{-1|0|1, string} $right
     * @return -1|0|1
     */
    private static function compareSignedInteger(array $left, array $right): int
    {
        [$leftSign, $leftMagnitude] = $left;
        [$rightSign, $rightMagnitude] = $right;

        if ($leftSign !== $rightSign) {
            return $leftSign <=> $rightSign;
        }

        if ($leftSign === 0) {
            return 0;
        }

        $comparison = self::compareUnsigned($leftMagnitude, $rightMagnitude);

        return $leftSign > 0 ? $comparison : -$comparison;
    }

    /** @return -1|0|1 */
    private static function compareSignificands(string $left, string $right): int
    {
        $leftLength = strlen($left);
        $rightLength = strlen($right);
        $length = max($leftLength, $rightLength);

        for ($i = 0; $i < $length; $i++) {
            $leftDigit = $i < $leftLength ? ord($left[$i]) : 48;
            $rightDigit = $i < $rightLength ? ord($right[$i]) : 48;

            if ($leftDigit !== $rightDigit) {
                return $leftDigit <=> $rightDigit;
            }
        }

        return 0;
    }

    /** @return -1|0|1 */
    private static function compareUnsigned(string $left, string $right): int
    {
        $lengthComparison = strlen($left) <=> strlen($right);

        if ($lengthComparison !== 0) {
            return $lengthComparison;
        }

        return strcmp($left, $right) <=> 0;
    }

    private static function addUnsigned(string $left, string $right): string
    {
        $leftIndex = strlen($left) - 1;
        $rightIndex = strlen($right) - 1;
        $carry = 0;
        $result = '';

        while ($leftIndex >= 0 || $rightIndex >= 0 || $carry !== 0) {
            $leftDigit = $leftIndex >= 0 ? ord($left[$leftIndex]) - 48 : 0;
            $rightDigit = $rightIndex >= 0 ? ord($right[$rightIndex]) - 48 : 0;
            $sum = $leftDigit + $rightDigit + $carry;
            $result .= (string) ($sum % 10);
            $carry = intdiv($sum, 10);
            $leftIndex--;
            $rightIndex--;
        }

        return strrev($result);
    }

    /**
     * Subtracts two unsigned decimal integers. $left must be >= $right.
     */
    private static function subtractUnsigned(string $left, string $right): string
    {
        $leftIndex = strlen($left) - 1;
        $rightIndex = strlen($right) - 1;
        $borrow = 0;
        $result = '';

        while ($leftIndex >= 0) {
            $leftDigit = ord($left[$leftIndex]) - 48 - $borrow;
            $rightDigit = $rightIndex >= 0 ? ord($right[$rightIndex]) - 48 : 0;

            if ($leftDigit < $rightDigit) {
                $leftDigit += 10;
                $borrow = 1;
            } else {
                $borrow = 0;
            }

            $result .= (string) ($leftDigit - $rightDigit);
            $leftIndex--;
            $rightIndex--;
        }

        $result = ltrim(strrev($result), '0');

        return $result === '' ? '0' : $result;
    }
}
