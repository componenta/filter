<?php

declare(strict_types=1);

namespace Componenta\Filter;

use Componenta\Arrayable\Arrayable;

/**
 * Interface FilterInterface
 *
 * Represents a filter that works on an iterable collection.
 * Extends IteratorAggregate for direct iteration over filtered results.
 */
interface FilterInterface extends \IteratorAggregate, Arrayable
{
    /**
     * Sets the iterable data source to be filtered.
     *
     * @param iterable $iterable The data source to filter.
     * @return static Returns a new filter instance with the updated iterable.
     */
    public function withIterable(iterable $iterable): static;

    /**
     * Evaluates whether a given element meets the filter criteria.
     *
     * @param mixed $value The element to check.
     * @param string|int|null $key The key associated with the element.
     * @return bool True if the element satisfies the filter criteria.
     */
    public function accept(mixed $value, string|int|null $key = null): bool;

    /**
     * Converts the filtered result set to an array.
     *
     * @param bool $preserveKeys Whether to preserve original keys.
     * @return array The filtered items as an array.
     */
    public function toArray(bool $preserveKeys = false): array;
}
