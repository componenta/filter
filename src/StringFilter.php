<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that are strings or implement Stringable.
 */
final class StringFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return is_string($value) || $value instanceof \Stringable;
    }
}
