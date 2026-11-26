<?php

declare(strict_types=1);

namespace Componenta\Filter;

/** @internal */
final class FloatDecimalString
{
    private function __construct()
    {
    }

    public static function from(float $value): string
    {
        $serializePrecision = ini_get('serialize_precision');

        if ($serializePrecision === '-1') {
            return json_encode(
                $value,
                JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
            );
        }

        $previous = ini_set('serialize_precision', '-1');

        if ($previous === false) {
            return sprintf('%.17h', $value);
        }

        try {
            return json_encode(
                $value,
                JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
            );
        } finally {
            ini_set('serialize_precision', $previous);
        }
    }
}
