<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * MergingFilter combines results from multiple filters.
 *
 * Each filter operates on its own data source. Results are merged
 * in the order filters were added (using yield from).
 */
class MergingFilter implements FilterInterface
{
    /** @var FilterInterface[] */
    private array $filters = [];

    /**
     * @param FilterInterface ...$filters Filters whose results will be merged.
     */
    public function __construct(FilterInterface ...$filters)
    {
        $this->filters = $filters;
    }

    /**
     * Adds a filter to the merge chain.
     *
     * @param FilterInterface $filter The filter to add.
     * @return static New instance with the added filter.
     */
    public function withFilter(FilterInterface $filter): static
    {
        $copy = clone $this;
        $copy->filters[] = $filter;
        return $copy;
    }

    /**
     * Removes a filter from the merge chain.
     *
     * @param FilterInterface $filter The filter to remove.
     * @return static New instance without the specified filter.
     */
    public function withoutFilter(FilterInterface $filter): static
    {
        $copy = clone $this;
        $copy->filters = array_values(
            array_filter($this->filters, static fn($f) => $f !== $filter)
        );
        return $copy;
    }

    /**
     * Returns all filters in the chain.
     *
     * @return FilterInterface[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Iterates over all filters, yielding their results sequentially.
     *
     * @return \Generator
     */
    public function getIterator(): \Generator
    {
        foreach ($this->filters as $filter) {
            yield from $filter->getIterator();
        }
    }

    /**
     * MergingFilter itself does not filter - each inner filter does.
     *
     * @param mixed $value The value (unused).
     * @param string|int|null $key The key (unused).
     * @return bool Always returns true.
     */
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return true;
    }

    /**
     * Not applicable for MergingFilter - use constructor instead.
     *
     * @param iterable $iterable Ignored.
     * @return static Returns self (no-op).
     */
    public function withIterable(iterable $iterable): static
    {
        return $this;
    }

    /**
     * Converts merged results to an array.
     *
     * @param bool $preserveKeys If true, preserve original keys (may cause overwrites).
     * @return array
     */
    public function toArray(bool $preserveKeys = false): array
    {
        return iterator_to_array($this->getIterator(), $preserveKeys);
    }
}
