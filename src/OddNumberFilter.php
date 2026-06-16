<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that are odd integers.
 */
final class OddNumberFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $intValue = (int) $value;
        
        // Ensure it's actually an integer (not a float like 3.5)
        if ((float) $value !== (float) $intValue) {
            return false;
        }

        return $intValue % 2 !== 0;
    }
}
