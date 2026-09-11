<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose string value contains only alphanumeric characters.
 */
final class AlphaNumericFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        $stringValue = StringValue::from($value);

        return $stringValue !== null && ctype_alnum($stringValue);
    }
}
