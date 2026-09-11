<?php

declare(strict_types=1);

namespace Componenta\Filter;

/** @internal */
final class IntegerParity
{
    private function __construct()
    {
    }

    public static function of(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value % 2 === 0 ? 0 : 1;
        }

        if (is_string($value)) {
            return self::ofNumericString($value);
        }

        if (!is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        if (!is_finite($number) || floor($number) !== $number) {
            return null;
        }

        return fmod(abs($number), 2.0) === 0.0 ? 0 : 1;
    }

    private static function ofNumericString(string $value): ?int
    {
        $value = trim($value);

        if (preg_match(
            '/^[+-]?(?:(\d+)(?:\.(\d*))?|\.(\d+))(?:[eE]([+-]?\d+))?$/D',
            $value,
            $matches,
            PREG_UNMATCHED_AS_NULL,
        ) !== 1) {
            return null;
        }

        if ($matches[1] !== null) {
            $integer = $matches[1];
            $fraction = $matches[2] ?? '';
        } else {
            $integer = '0';
            $fraction = $matches[3];
        }

        $digits = $integer . $fraction;

        if (strspn($digits, '0') === strlen($digits)) {
            return 0;
        }

        [$exponentNegative, $exponentMagnitude] = self::parseExponent($matches[4] ?? null);
        $fractionLength = strlen($fraction);
        $digitsLength = strlen($digits);

        if (!$exponentNegative) {
            $comparison = self::compareUnsignedDecimalToInt($exponentMagnitude, $fractionLength);

            if ($comparison > 0) {
                // A positive decimal shift appends at least one zero.
                return 0;
            }

            $scale = $fractionLength - (int) $exponentMagnitude;
        } else {
            $integerLength = $digitsLength - $fractionLength;

            if (self::compareUnsignedDecimalToInt($exponentMagnitude, $integerLength) >= 0) {
                // A non-zero value shifted left beyond all integer digits is fractional.
                return null;
            }

            $scale = $fractionLength + (int) $exponentMagnitude;
        }

        if ($scale === 0) {
            return ((int) $digits[$digitsLength - 1]) % 2;
        }

        if ($scale >= $digitsLength) {
            return null;
        }

        if (strspn($digits, '0', $digitsLength - $scale, $scale) !== $scale) {
            return null;
        }

        return ((int) $digits[$digitsLength - $scale - 1]) % 2;
    }

    /** @return array{bool, string} */
    private static function parseExponent(?string $exponent): array
    {
        if ($exponent === null || $exponent === '') {
            return [false, '0'];
        }

        $negative = $exponent[0] === '-';

        if ($exponent[0] === '+' || $negative) {
            $exponent = substr($exponent, 1);
        }

        $magnitude = ltrim($exponent, '0');

        if ($magnitude === '') {
            return [false, '0'];
        }

        return [$negative, $magnitude];
    }

    private static function compareUnsignedDecimalToInt(string $decimal, int $integer): int
    {
        $integerString = (string) $integer;
        $lengthComparison = strlen($decimal) <=> strlen($integerString);

        if ($lengthComparison !== 0) {
            return $lengthComparison;
        }

        return $decimal <=> $integerString;
    }
}
