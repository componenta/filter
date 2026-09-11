<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Base class for predicate-backed collection filters.
 */
abstract class AbstractFilter extends AbstractCollectionFilter implements FilterInterface
{
    public function getIterator(): \Generator
    {
        foreach ($this->iterable as $key => $value) {
            if ($this->accept($value, $key)) {
                yield $key => $value;
            }
        }
    }

    abstract public function accept(mixed $value, string|int|null $key = null): bool;
}
