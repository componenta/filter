<?php

declare(strict_types=1);

namespace Componenta\Filter;

/** @internal */
final class StringValue
{
    private function __construct()
    {
    }

    public static function from(mixed $value): ?string
    {
        if (is_array($value)) {
            return null;
        }

        if (is_object($value) && !$value instanceof \Stringable) {
            return null;
        }

        return (string) $value;
    }
}
