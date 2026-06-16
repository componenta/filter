<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that are empty (as defined by PHP's empty()).
 */
final class EmptyFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return empty($value);
    }
}
