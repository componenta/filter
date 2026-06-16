<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that are scalar values (int, float, string, bool).
 */
final class ScalarFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return is_scalar($value);
    }
}
