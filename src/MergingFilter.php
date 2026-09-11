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

    public function withFilter(FilterInterface $filter): static
    {
        $copy = clone $this;
        $copy->filters[] = $filter;

        return $copy;
    }

    public function withoutFilter(FilterInterface $filter): static
    {
        $copy = clone $this;
        $copy->filters = array_values(
            array_filter($this->filters, static fn(FilterInterface $candidate): bool => $candidate !== $filter),
        );

        return $copy;
    }

    /** @return FilterInterface[] */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getIterator(): \Generator
    {
        foreach ($this->filters as $filter) {
            yield from $filter->getIterator();
        }
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter->accept($value, $key)) {
                return true;
            }
        }

        return false;
    }

    public function withIterable(iterable $iterable): static
    {
        $copy = clone $this;
        $copy->filters = array_map(
            static fn(FilterInterface $filter): FilterInterface => $filter->withIterable($iterable),
            $this->filters,
        );

        return $copy;
    }

    public function toArray(bool $preserveKeys = false): array
    {
        return iterator_to_array($this->getIterator(), $preserveKeys);
    }
}
