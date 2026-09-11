<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts finite numeric values and numeric strings.
 */
final class NumericFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return NumericValueComparator::isValid($value);
    }
}
