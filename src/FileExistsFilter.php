<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that represent existing file paths.
 */
final class FileExistsFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        $path = StringValue::from($value);

        return $path !== null && file_exists($path);
    }
}
