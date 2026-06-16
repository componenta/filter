<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that are not empty.
 */
final class NotEmptyFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return !empty($value);
    }
}
