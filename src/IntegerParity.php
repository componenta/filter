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
            $normalized = trim($value);

            if (preg_match('/^[+-]?\d+$/D', $normalized) === 1) {
                $lastDigit = (int) $normalized[strlen($normalized) - 1];

                return $lastDigit % 2;
            }
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
}
