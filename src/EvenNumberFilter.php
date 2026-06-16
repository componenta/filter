<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that are even integers.
 */
final class EvenNumberFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $intValue = (int) $value;
        
        // Ensure it's actually an integer (not a float like 2.5)
        if ((float) $value !== (float) $intValue) {
            return false;
        }

        return $intValue % 2 === 0;
    }
}
