<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts only unique elements (first occurrence).
 *
 * Direct accept() calls retain predicate state. Each iterator keeps its own
 * uniqueness state so concurrent iterations over one filter do not interfere.
 */
final class UniqueFilter extends AbstractFilter
{
    /** @var array<int, mixed> */
    private array $seen = [];

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (in_array($value, $this->seen, true)) {
            return false;
        }

        $this->seen[] = $value;

        return true;
    }

    public function getIterator(): \Generator
    {
        $this->seen = [];

        return self::iterateUnique($this->iterable);
    }

    public function __clone(): void
    {
        $this->seen = [];
    }

    private static function iterateUnique(iterable $iterable): \Generator
    {
        $seen = [];

        foreach ($iterable as $key => $value) {
            if (in_array($value, $seen, true)) {
                continue;
            }

            $seen[] = $value;
            yield $key => $value;
        }
    }
}
