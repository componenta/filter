<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that are floats.
 */
final class FloatFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return is_float($value);
    }
}
