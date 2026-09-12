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
            $key = self::predicateKey($key);

            if ($this->accept($value, $key)) {
                yield $key => $value;
            }
        }
    }

    abstract public function accept(mixed $value, string|int|null $key = null): bool;

    protected static function predicateKey(mixed $key): string|int|null
    {
        if (!is_string($key) && !is_int($key) && $key !== null) {
            throw new \UnexpectedValueException(sprintf(
                'Predicate-backed filters require iterable keys to be string, int, or null; %s given',
                get_debug_type($key),
            ));
        }

        return $key;
    }
}
