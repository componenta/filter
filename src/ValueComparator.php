<?php

declare(strict_types=1);

namespace Componenta\Filter;

/** @internal */
final class ValueComparator
{
    private function __construct()
    {
    }

    public static function equals(mixed $left, mixed $right, bool $strict = true): bool
    {
        try {
            return $strict ? $left === $right : $left == $right;
        } catch (\Error) {
            return false;
        }
    }

    public static function contains(array $values, mixed $needle, bool $strict = true): bool
    {
        foreach ($values as $candidate) {
            if (self::equals($needle, $candidate, $strict)) {
                return true;
            }
        }

        return false;
    }
}
