<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * OneOfFilter applies multiple filters with OR-logic.
 *
 * An element is accepted if AT LEAST ONE filter accepts it.
 */
class OneOfFilter extends AbstractFilter implements FilterableInterface
{
    use Filterable {
        accept as private traitAccept;
    }

    /**
     * @param FilterInterface[] $filters Filters to apply (OR-logic).
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
     * Creates a OneOfFilter from variadic filter arguments.
     *
     * @param FilterInterface ...$filters Filters to use.
     * @return static
     */
    public static function create(FilterInterface ...$filters): static
    {
        return new static($filters);
    }

    /**
     * Creates a OneOfFilter with data source and filters.
     *
     * @param iterable $iterable The data source.
     * @param FilterInterface ...$filters Filters to apply.
     * @return static
     */
    public static function from(iterable $iterable, FilterInterface ...$filters): static
    {
        return new static($filters, $iterable);
    }

    /**
     * Determines whether an element is accepted by at least one filter.
     *
     * @param mixed $value The element to evaluate.
     * @param string|int|null $key The key associated with the element.
     * @return bool True if at least one filter accepts the element.
     */
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (empty($this->filters)) {
            return true;
        }

        foreach ($this->filters as $filter) {
            if ($filter->accept($value, $key)) {
                return true;
            }
        }
        return false;
    }
}
