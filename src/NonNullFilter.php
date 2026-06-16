<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that are not null.
 */
final class NonNullFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return $value !== null;
    }
}
