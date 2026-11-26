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
        $scalars = [];
        $objects = new \SplObjectStorage();
        $other = [];

        foreach ($this->iterable as $key => $value) {
            if (is_float($value) && is_nan($value)) {
                // NaN is not strictly equal to itself.
                yield $key => $value;
                continue;
            }

            $scalarKey = match (true) {
                $value === null => 'n:',
                is_bool($value) => $value ? 'b:1' : 'b:0',
                is_int($value) => 'i:' . $value,
                is_string($value) => 's:' . $value,
                is_float($value) => 'f:' . pack('d', $value == 0.0 ? 0.0 : $value),
                default => null,
            };

            if ($scalarKey !== null) {
                if (isset($scalars[$scalarKey])) {
                    continue;
                }

                $scalars[$scalarKey] = true;
            } elseif (is_object($value)) {
                if ($objects->offsetExists($value)) {
                    continue;
                }

                // Retain identity, preventing object ID reuse during traversal.
                $objects->offsetSet($value, null);
            } else {
                // Keep PHP's strict semantics for arrays and resources,
                // including recursive arrays and resources closed mid-traversal.
                if (ValueComparator::contains($other, $value)) {
                    continue;
                }

                $other[] = $value;
            }

            yield $key => $value;
        }
    }
}
