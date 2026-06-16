<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * ChainableFilter applies multiple filters with AND-logic.
 *
 * An element is accepted only if ALL filters in the chain accept it.
 * This class is immutable.
 */
class ChainableFilter extends AbstractFilter implements FilterableInterface
{
    use Filterable;

    /**
     * @param FilterInterface[] $filters Filters to apply (AND-logic).
     * @param iterable $iterable The data source to filter.
     */
    public function __construct(
        iterable $filters = [],
        iterable $iterable = [],
    ) {
        foreach ($filters as $filter) {
            if (!$filter instanceof FilterInterface) {
                throw new \InvalidArgumentException(
                    sprintf('Expected FilterInterface, got %s', get_debug_type($filter))
                );
            }
            $this->filters[] = $filter;
        }
        parent::__construct($iterable);
    }

    /**
     * Creates a ChainableFilter from variadic filter arguments.
     *
     * @param FilterInterface ...$filters Filters to chain.
     * @return static
     */
    public static function create(FilterInterface ...$filters): static
    {
        return new static($filters);
    }

    /**
     * Creates a ChainableFilter with data source and filters.
     *
     * @param iterable $iterable The data source.
     * @param FilterInterface ...$filters Filters to apply.
     * @return static
     */
    public static function from(iterable $iterable, FilterInterface ...$filters): static
    {
        return new static($filters, $iterable);
    }
}
