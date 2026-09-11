<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that represent existing directories.
 */
final class DirectoryFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        $path = StringValue::from($value);

        return $path !== null && is_dir($path);
    }
}
