<?php

declare(strict_types=1);

namespace Componenta\Filter;

/** @internal */
final class ArrayValueComparator
{
    private function __construct()
    {
    }

    /**
     * Index only configuration values with stable string representations.
     * Floats depend on runtime precision; objects may execute user code.
     * Keep the original comparison order for any heterogeneous list that
     * includes such values or values requiring strict identity comparison.
     *
     * @return array<string, true>|null
     */
    public static function prepareScalarIndex(array $values): ?array
    {
        $index = [];

        foreach ($values as $value) {
            if ($value !== null && !is_string($value) && !is_int($value) && !is_bool($value)) {
                return null;
            }

            $index['s:' . (string) $value] = true;
        }

        return $index;
    }

    public static function contains(array $values, mixed $needle, ?array $index = null): bool
    {
        $needleString = self::comparisonString($needle);

        if ($index !== null) {
            return $needleString !== null && isset($index['s:' . $needleString]);
        }

        foreach ($values as $candidate) {
            $candidateString = self::comparisonString($candidate);

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

    private static function comparisonString(mixed $value): ?string
    {
        if ($value === null
            || is_scalar($value)
            || is_resource($value)
            || gettype($value) === 'resource (closed)'
        ) {
            return (string) $value;
        }

        return $value instanceof \Stringable
            ? (string) $value
            : null;
    }
}
