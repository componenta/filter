<?php

declare(strict_types=1);

namespace Componenta\Filter;

/** @internal */
final class FloatDecimalString
{
    private function __construct()
    {
    }

    public static function from(float $value): string
    {
        $serializePrecision = function_exists('ini_get') ? ini_get('serialize_precision') : false;

        if ($serializePrecision === '-1') {
            return json_encode(
                $value,
                JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
            );
        }

        if (!function_exists('ini_set')) {
            return self::roundTrip($value);
        }

        $previous = ini_set('serialize_precision', '-1');

        if ($previous === false) {
            return self::roundTrip($value);
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
     * Used only when the runtime does not permit selecting JSON's shortest
     * decimal mode. Search increasing significant-digit counts. The nearest
     * rounded decimal is tested first, then its neighbor toward the float.
     * The second check is needed at asymmetric binary rounding boundaries
     * (in particular, exact powers of two).
     */
    private static function roundTrip(float $value): string
    {
        if ($value == 0.0) {
            return '0';
        }

        $sign = $value < 0.0 ? '-' : '';
        $value = abs($value);

        // Seventeen significant decimal digits suffice for binary64.
        for ($precision = 1; $precision <= 17; $precision++) {
            // h is locale independent; g would depend on LC_NUMERIC.
            $nearest = sprintf('%.' . $precision . 'h', $value);
            $rounded = (float) $nearest;

            if ($rounded === $value) {
                return $sign . $nearest;
            }

            // Recover the decimal grid at this precision even if sprintf
            // removed trailing zeros or selected fixed-point notation.
            [$mantissa, $exponent] = explode('e', strtolower($nearest), 2) + [1 => '0'];
            $dot = strpos($mantissa, '.');
            $scale = (int) $exponent - ($dot === false ? 0 : strlen($mantissa) - $dot - 1);
            $digits = ltrim(str_replace('.', '', $mantissa), '0');
            $significant = rtrim($digits, '0');
            $scale += strlen($digits) - strlen($significant);
            $padding = $precision - strlen($significant);
            $digits = $significant . str_repeat('0', $padding);
            $scale -= $padding;

            if ($rounded < $value) {
                $adjacent = str_increment($digits);
            } elseif ($digits === '1' . str_repeat('0', $precision - 1)) {
                // The previous grid point crosses a decimal decade.
                $adjacent = str_repeat('9', $precision);
                $scale--;
            } else {
                $adjacent = str_decrement($digits);
            }

            $candidate = $adjacent . 'e' . $scale;

            if ((float) $candidate === $value) {
                return $sign . $candidate;
            }
        }

        throw new \LogicException('Unable to format a finite float as a round-trip decimal');
    }
}
