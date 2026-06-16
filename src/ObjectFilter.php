<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that are objects.
 */
final class ObjectFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return is_object($value);
    }
}
