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
        if (is_string($value)) {
            return $value;
        }

        return $value instanceof \Stringable
            ? (string) $value
            : null;
    }
}
