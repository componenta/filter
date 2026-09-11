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

            if ($needle === $candidate) {
                return true;
            }
        }

        return false;
    }
}
