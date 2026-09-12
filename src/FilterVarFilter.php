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
        $defaultFailureSentinel = self::replaceValidationDefault($this->filter, $options);
        $boolean = $this->filter === FILTER_VALIDATE_BOOLEAN;
        $throwOnFailure = self::hasThrowOnFailure(self::flags($options))
            && self::isValidationFilter($this->filter);

        if ($boolean && !$throwOnFailure) {
            if (is_array($options)) {
                $options['flags'] = ($options['flags'] ?? 0) | FILTER_NULL_ON_FAILURE;
            } else {
                $options |= FILTER_NULL_ON_FAILURE;
            }
        }

        try {
            $result = filter_var($value, $this->filter, $options);
        } catch (\Filter\FilterFailedException) {
            return false;
        }

        if ($defaultFailureSentinel !== null
            && self::containsSentinel($result, $defaultFailureSentinel)
        ) {
            return false;
        }

        $flags = self::flags($options);
        $arrayMode = ($flags & (FILTER_REQUIRE_ARRAY | FILTER_FORCE_ARRAY)) !== 0;
        $nullOnFailure = self::returnsNullOnFailure($this->filter, $flags);

        if ($throwOnFailure) {
            return $arrayMode ? is_array($result) : true;
        }

        if ($arrayMode) {
            return is_array($result) && !self::containsFailure($result, $nullOnFailure);
        }

        return $nullOnFailure ? $result !== null : $result !== false;
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

        $flags = self::flags($options);

        if (self::hasThrowOnFailure($flags)
            && ($flags & FILTER_NULL_ON_FAILURE) !== 0
        ) {
            throw new \InvalidArgumentException(
                'FILTER_THROW_ON_FAILURE cannot be combined with FILTER_NULL_ON_FAILURE',
            );
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

    private static function replaceValidationDefault(int $filter, array|int &$options): ?object
    {
        if (!self::isValidationFilter($filter)
            || !is_array($options)
            || !is_array($options['options'] ?? null)
            || !array_key_exists('default', $options['options'])
        ) {
            return null;
        }

        $sentinel = new \stdClass();
        $options['options']['default'] = $sentinel;

        return $sentinel;
    }

    private static function containsSentinel(mixed $value, object $sentinel): bool
    {
        if ($value === $sentinel) {
            return true;
        }

        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (self::containsSentinel($item, $sentinel)) {
                return true;
            }
        }

        return false;
    }

    private static function flags(array|int $options): int
    {
        return is_int($options) ? $options : ($options['flags'] ?? 0);
    }

    private static function hasThrowOnFailure(int $flags): bool
    {
        if (!defined('FILTER_THROW_ON_FAILURE')) {
            return false;
        }

        /** @var int $throwOnFailure */
        $throwOnFailure = constant('FILTER_THROW_ON_FAILURE');

        return ($flags & $throwOnFailure) !== 0;
    }

    private static function returnsNullOnFailure(int $filter, int $flags): bool
    {
        return ($flags & FILTER_NULL_ON_FAILURE) !== 0
            && self::isValidationFilter($filter);
    }

    private static function isValidationFilter(int $filter): bool
    {
        return in_array($filter, [
            FILTER_VALIDATE_INT,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_VALIDATE_FLOAT,
            FILTER_VALIDATE_REGEXP,
            FILTER_VALIDATE_DOMAIN,
            FILTER_VALIDATE_URL,
            FILTER_VALIDATE_EMAIL,
            FILTER_VALIDATE_IP,
            FILTER_VALIDATE_MAC,
        ], true);
    }

    private static function containsFailure(array $values, bool $nullOnFailure): bool
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                if (self::containsFailure($value, $nullOnFailure)) {
                    return true;
                }

                continue;
            }

            if ($nullOnFailure ? $value === null : $value === false) {
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
