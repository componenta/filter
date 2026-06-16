<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Interface FilterableInterface
 *
 * Contract for objects that can have filters added or removed.
 * Implementations should be immutable, returning new instances.
 */
interface FilterableInterface
{
    /**
     * Returns a new instance with the specified filter added.
     *
     * @param FilterInterface $filter The filter to add.
     * @param bool $prepend If true, add at the beginning of the chain.
     * @return static New instance with the added filter.
     */
    public function withFilter(FilterInterface $filter, bool $prepend = false): static;

    /**
     * Checks whether the given filter exists in the chain.
     *
     * @param FilterInterface $filter The filter to search for.
     * @return bool True if the filter is found.
     */
    public function hasFilter(FilterInterface $filter): bool;

    /**
     * Returns a new instance without the specified filter.
     *
     * @param FilterInterface $filter The filter to remove.
     * @return static New instance without the specified filter.
     */
    public function withoutFilter(FilterInterface $filter): static;
}
