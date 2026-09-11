<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that represent even integers.
 */
final class EvenNumberFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return IntegerParity::of($value) === 0;
    }
}
