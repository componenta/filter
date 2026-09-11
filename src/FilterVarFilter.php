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

        self::assertValidOptions($filter, $options);
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

    private static function assertValidOptions(int $filter, array|int $options): void
    {
        if ($filter === FILTER_VALIDATE_REGEXP) {
            $regexp = is_array($options)
                && is_array($options['options'] ?? null)
                ? ($options['options']['regexp'] ?? null)
                : null;

            if (!is_string($regexp) || !self::isValidRegexp($regexp)) {
                throw new \InvalidArgumentException('FILTER_VALIDATE_REGEXP requires a valid regexp option');
            }
        }

        if ($filter === FILTER_CALLBACK) {
            $callback = is_array($options) ? ($options['options'] ?? null) : null;

            if (!is_callable($callback)) {
                throw new \InvalidArgumentException('FILTER_CALLBACK requires a callable option');
            }
        }
    }

    private static function isValidRegexp(string $regexp): bool
    {
        set_error_handler(static fn(): bool => true, E_WARNING);

        try {
            return preg_match($regexp, '') !== false;
        } finally {
            restore_error_handler();
        }
    }
}
