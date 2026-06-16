<?php

declare(strict_types=1);

namespace Componenta\Filter;

trait Filterable
{
    /** @var FilterInterface[] */
    protected array $filters = [];

    /**
     * @param iterable<FilterInterface>|FilterInterface $filters
     *
     * @throws \InvalidArgumentException If any element does not implement FilterInterface.
     */
    protected function initFilters(iterable|FilterInterface $filters): void
    {
        if ($filters instanceof FilterInterface) {
            $this->filters[] = $filters;

            return;
        }

        foreach ($filters as $i => $filter) {
            if (!$filter instanceof FilterInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        '$filters[%s] passed to %s must implement FilterInterface',
                        $i,
                        static::class,
                    ),
                );
            }

            $this->filters[] = $filter;
        }
    }

    public function withFilter(FilterInterface $filter, bool $prepend = false): static
    {
        $copy = clone $this;

        if ($prepend) {
            array_unshift($copy->filters, $filter);
        } else {
            $copy->filters[] = $filter;
        }

        return $copy;
    }

    public function hasFilter(FilterInterface $filter): bool
    {
        return in_array($filter, $this->filters, true);
    }

    public function accept(mixed $value, int|string|null $key = null): bool
    {
        foreach ($this->filters as $filter) {
            if (!$filter->accept($value, $key)) {
                return false;
            }
        }

        return true;
    }

    public function withoutFilter(FilterInterface $filter): static
    {
        $copy = clone $this;
        $copy->filters = array_values(
            array_filter($this->filters, static fn($f) => $f !== $filter),
        );

        return $copy;
    }

    /** @return FilterInterface[] */
    public function getFilters(): array
    {
        return $this->filters;
    }
}