<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Applies multiple predicates with AND semantics.
 */
class ChainableFilter extends AbstractFilter implements FilterableInterface
{
    use Filterable;

    /**
     * @param iterable<PredicateInterface> $filters
     */
    public function __construct(
        iterable $filters = [],
        iterable $iterable = [],
    ) {
        $this->initFilters($filters);
        parent::__construct($iterable);
    }

    public static function create(PredicateInterface ...$filters): static
    {
        return new static($filters);
    }

    public static function from(iterable $iterable, PredicateInterface ...$filters): static
    {
        return new static($filters, $iterable);
    }
}
