<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Inverts the result of another filter.
 * Accepts elements that the inner filter rejects.
 */
final class NotFilter extends AbstractFilter
{
    public function __construct(
        private readonly FilterInterface $filter,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withFilter(FilterInterface $filter): static
    {
        return new self($filter, $this->iterable);
    }

    public function getFilter(): FilterInterface
    {
        return $this->filter;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return !$this->filter->accept($value, $key);
    }
}
