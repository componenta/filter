<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Inverts another predicate.
 */
final class NotFilter extends AbstractFilter
{
    public function __construct(
        private readonly PredicateInterface $filter,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withFilter(PredicateInterface $filter): static
    {
        return new self($filter, $this->iterable);
    }

    public function getFilter(): PredicateInterface
    {
        return $this->filter;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return !$this->filter->accept($value, $key);
    }
}
