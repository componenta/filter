<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that are null.
 */
final class NullFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return $value === null;
    }
}
