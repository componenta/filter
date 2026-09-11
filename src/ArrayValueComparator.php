<?php

declare(strict_types=1);

namespace Componenta\Filter;

/** @internal */
final class ArrayValueComparator
{
    private function __construct()
    {
    }

    public static function contains(array $values, mixed $needle): bool
    {
        $needleString = StringValue::from($needle);

        foreach ($values as $candidate) {
            $candidateString = StringValue::from($candidate);

            if ($needleString !== null && $candidateString !== null) {
                if ($needleString === $candidateString) {
                    return true;
                }

                continue;
            }

            try {
                if ($needle === $candidate) {
                    return true;
                }
            } catch (\Error) {
                // Recursive arrays cannot be compared strictly without an Error.
                // Treat them as non-equal rather than failing the filter operation.
            }
        }

        return false;
    }
}
