<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that are integers.
 */
final class IntFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return is_int($value);
    }
}
