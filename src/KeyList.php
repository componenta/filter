<?php

declare(strict_types=1);

namespace Componenta\Filter;

/** @internal */
final class KeyList
{
    private function __construct()
    {
    }

    public static function assert(array $values, string $argument): void
    {
        foreach ($values as $index => $value) {
            if (!is_string($value) && !is_int($value) && $value !== null) {
                throw new \InvalidArgumentException(sprintf(
                    '%s[%s] must be string, int, or null; %s given',
                    $argument,
                    $index,
                    get_debug_type($value),
                ));
            }
        }
    }
}
