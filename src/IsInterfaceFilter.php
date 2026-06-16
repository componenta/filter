<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that are valid interface names.
 */
final class IsInterfaceFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return is_string($value) && interface_exists($value);
    }
}
