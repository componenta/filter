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
        if (!self::isKnownFilter($filter)) {
            throw new \InvalidArgumentException(sprintf('Unknown filter id: %d', $filter));
        }

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
        if ($this->filter !== FILTER_VALIDATE_BOOLEAN) {
            return filter_var($value, $this->filter, $this->options) !== false;
        }

        $options = $this->options;

        if (is_array($options)) {
            $options['flags'] = ($options['flags'] ?? 0) | FILTER_NULL_ON_FAILURE;
        } else {
            $options |= FILTER_NULL_ON_FAILURE;
        }

        return filter_var($value, $this->filter, $options) !== null;
    }

    private static function isKnownFilter(int $filter): bool
    {
        static $filterIds = null;

        $filterIds ??= array_map(filter_id(...), filter_list());

        return in_array($filter, $filterIds, true);
    }
}
