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
        return file_exists((string) $value);
    }
}
