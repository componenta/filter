<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that pass PHP's filter_var() validation.
 */
final class FilterVarFilter extends AbstractFilter
{
    public function __construct(
        private readonly int $filter,
        private readonly array|int $options = 0,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withFilter(int $filter): static
    {
        return new self($filter, $this->options, $this->iterable);
    }

    public function withOptions(array|int $options): static
    {
        return new self($this->filter, $options, $this->iterable);
    }

    public function getFilter(): int
    {
        return $this->filter;
    }

    public function getOptions(): array|int
    {
        return $this->options;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return filter_var($value, $this->filter, $this->options) !== false;
    }
}
