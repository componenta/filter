<?php

declare(strict_types=1);

namespace Componenta\Filter;

trait Filterable
{
    /** @var PredicateInterface[] */
    protected array $filters = [];

    /**
     * @param iterable<PredicateInterface>|PredicateInterface $filters
     */
    protected function initFilters(iterable|PredicateInterface $filters): void
    {
        if ($filters instanceof PredicateInterface) {
            $this->filters[] = $filters;

            return;
        }

        foreach ($filters as $i => $filter) {
            if (!$filter instanceof PredicateInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        '$filters[%s] passed to %s must implement PredicateInterface',
                        $i,
                        static::class,
                    ),
                );
            }

            $this->filters[] = $filter;
        }
    }

    public function withFilter(PredicateInterface $filter, bool $prepend = false): static
    {
        $copy = clone $this;

        if ($prepend) {
            array_unshift($copy->filters, $filter);
        } else {
            $copy->filters[] = $filter;
        }

        return $copy;
    }

    public function hasFilter(PredicateInterface $filter): bool
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

    public function withoutFilter(PredicateInterface $filter): static
    {
        $copy = clone $this;
        $copy->filters = array_values(
            array_filter($this->filters, static fn(PredicateInterface $candidate): bool => $candidate !== $filter),
        );

        return $copy;
    }

    /** @return PredicateInterface[] */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
