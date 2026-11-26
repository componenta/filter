<?php

declare(strict_types=1);

namespace Componenta\Filter;

/** @internal */
final class CollectionArray
{
    private function __construct()
    {
    }

    public static function from(iterable $iterable, bool $preserveKeys): array
    {
        if (!$preserveKeys) {
            return iterator_to_array($iterable, false);
        }

        $result = [];

        foreach ($iterable as $key => $value) {
            // PHP 8.5 deprecates null array offsets. A null iterator key
            // remains valid and is normalized only when materialized.
            $result[$key ?? ''] = $value;
        }

        return $result;
    }
}
