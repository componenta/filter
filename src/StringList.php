<?php

declare(strict_types=1);

namespace Componenta\Filter;

/** @internal */
final class StringList
{
    private function __construct()
    {
    }

    public static function normalize(array $values, string $argument): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            if (!is_string($value)) {
                throw new \InvalidArgumentException(sprintf(
                    '%s[%s] must be a string, %s given',
                    $argument,
                    $key,
                    get_debug_type($value),
                ));
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
