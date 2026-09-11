<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that represent odd integers.
 */
final class OddNumberFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return IntegerParity::of($value) === 1;
    }
}
