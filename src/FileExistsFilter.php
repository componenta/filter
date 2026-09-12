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

        if ($path === null) {
            return false;
        }

        set_error_handler(static fn(): bool => true, E_WARNING);

        try {
            return file_exists($path);
        } finally {
            restore_error_handler();
        }
    }
}
