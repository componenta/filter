<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Keeps only the first occurrence of each value in an iterable.
 *
 * This is a collection operator, not a predicate: uniqueness depends on values
 * observed earlier in the same iteration.
 */
final class UniqueFilter extends AbstractCollectionFilter
{
    public function getIterator(): \Generator
    {
        $seen = [];

        foreach ($this->iterable as $key => $value) {
            if (ValueComparator::contains($seen, $value)) {
                continue;
            }

            $seen[] = $value;
            yield $key => $value;
        }
    }
}
