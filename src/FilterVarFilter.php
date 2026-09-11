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
        $options = $this->options;
        $boolean = $this->filter === FILTER_VALIDATE_BOOLEAN;

        if ($boolean) {
            if (is_array($options)) {
                $options['flags'] = ($options['flags'] ?? 0) | FILTER_NULL_ON_FAILURE;
            } else {
                $options |= FILTER_NULL_ON_FAILURE;
            }
        }

        $result = filter_var($value, $this->filter, $options);
        $flags = self::flags($options);
        $arrayMode = ($flags & (FILTER_REQUIRE_ARRAY | FILTER_FORCE_ARRAY)) !== 0;

        if ($arrayMode && is_array($result)) {
            return !self::containsFailure($result, $boolean);
        }

        return $boolean ? $result !== null : $result !== false;
    }

    private static function isKnownFilter(int $filter): bool
    {
        static $filterIds = null;

        $filterIds ??= array_map(filter_id(...), filter_list());

        return in_array($filter, $filterIds, true);
    }

    private static function assertValidOptions(int $filter, array|int $options): void
    {
        if (is_array($options)
            && array_key_exists('flags', $options)
            && !is_int($options['flags'])
        ) {
            throw new \InvalidArgumentException('filter_var flags must be an integer');
        }

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

    private static function flags(array|int $options): int
    {
        return is_int($options) ? $options : ($options['flags'] ?? 0);
    }

    private static function containsFailure(array $values, bool $boolean): bool
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                if (self::containsFailure($value, $boolean)) {
                    return true;
                }

                continue;
            }

            if ($boolean ? $value === null : $value === false) {
                return true;
            }
        }

        return false;
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
