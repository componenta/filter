<?php

declare(strict_types=1);

namespace Componenta\Filter;

/** @internal */
final class ConfigurationArray
{
    private function __construct()
    {
    }

    /**
     * Own the top-level slots while retaining the identity of arbitrary
     * values, including objects, resources and recursive nested arrays.
     */
    public static function copySlots(array $values): array
    {
        $copy = [];

        foreach ($values as $key => $value) {
            $copy[$key] = $value;
        }

        return $copy;
    }
}
