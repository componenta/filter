<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Applies multiple predicates with OR semantics.
 */
class OneOfFilter extends AbstractFilter implements FilterableInterface
{
    use Filterable {
        accept as private traitAccept;
    }

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

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter->accept($value, $key)) {
                return true;
            }
        }

        return false;
    }
}
