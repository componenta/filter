<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Abstract base class for filtering iterable collections.
 *
 * Provides a generator-based iterator that applies filtering logic
 * defined in the abstract accept() method.
 */
abstract class AbstractFilter implements FilterInterface
{
    /**
     * @param iterable $iterable The data source to filter.
     */
    public function __construct(
        protected iterable $iterable = []
    ) {
    }

    /**
     * Returns an iterator yielding elements that pass the filter.
     *
     * @return \Generator Yields filtered key-value pairs.
     */
    public function getIterator(): \Generator
    {
        foreach ($this->iterable as $key => $value) {
            if ($this->accept($value, $key)) {
                yield $key => $value;
            }
        }
    }

    /**
     * Returns a new instance with a different iterable data source.
     *
     * @param iterable $iterable The new data source.
     * @return static New filter instance with the provided iterable.
     */
    public function withIterable(iterable $iterable): static
    {
        $copy = clone $this;
        $copy->iterable = $iterable;

        return $copy;
    }

    /**
     * Converts the filtered results to an array.
     *
     * @param bool $preserveKeys If true, preserve original keys.
     * @return array Array of filtered elements.
     */
    public function toArray(bool $preserveKeys = false): array
    {
        return iterator_to_array($this->getIterator(), $preserveKeys);
    }

    /**
     * Determines whether the given element should be accepted.
     *
     * @param mixed $value The element to evaluate.
     * @param string|int|null $key The key associated with the element.
     * @return bool True if the element passes the filter.
     */
    abstract public function accept(mixed $value, string|int|null $key = null): bool;
}
