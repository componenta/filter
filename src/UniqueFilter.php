<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts only unique elements (first occurrence).
 * This filter is stateful.
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
        return parent::getIterator();
    }

    public function __clone(): void
    {
        $this->seen = [];
    }
}
