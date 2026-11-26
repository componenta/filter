<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that pass PHP's filter_var() validation.
 */
final class FilterVarFilter extends AbstractFilter
{
    private readonly array|int $options;

    public function __construct(
        private readonly int $filter,
        array|int $options = 0,
        iterable $iterable = []
    ) {
        if (!self::isKnownFilter($filter)) {
            throw new \InvalidArgumentException(sprintf('Unknown filter id: %d', $filter));
        }

        if (is_array($options)) {
            $options = ConfigurationArray::copySlots($options);

            if (is_array($options['options'] ?? null)) {
                $options['options'] = ConfigurationArray::copySlots($options['options']);
            }
        }

        self::assertValidOptions($filter, $options);
        $this->options = $options;
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

        if ($this->filter === FILTER_CALLBACK) {
            $options['flags'] = self::flags($options) & ~FILTER_NULL_ON_FAILURE;
        }

        $defaultFailureSentinel = self::replaceNativeDefault($this->filter, $options);
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

        $flags = self::flags($options);
        $arrayMode = ($flags & (FILTER_REQUIRE_ARRAY | FILTER_FORCE_ARRAY)) !== 0;
        $isArray = is_array($value);

        if ($isArray) {
            if (!$arrayMode || ($flags & FILTER_REQUIRE_SCALAR) !== 0) {
                return false;
            }
        } elseif (($flags & FILTER_REQUIRE_ARRAY) !== 0) {
            return false;
        }

        // Native array filtering writes through PHP references inside the
        // input array. A predicate must not change caller-owned values.
        // Preserve native scalar conversion and flag semantics, but evaluate
        // each array leaf independently without producing a transformed array.
        if ($arrayMode) {
            $flags &= ~(FILTER_REQUIRE_ARRAY | FILTER_FORCE_ARRAY);

            if (is_array($options)) {
                $options['flags'] = $flags;
            } else {
                $options = $flags;
            }
        }

        $nullOnFailure = self::returnsNullOnFailure($this->filter, $flags);

        try {
            return $isArray
                ? $this->acceptArray($value, $options, $defaultFailureSentinel, $throwOnFailure, $nullOnFailure)
                : $this->acceptScalar($value, $options, $defaultFailureSentinel, $throwOnFailure, $nullOnFailure);
        } catch (\Filter\FilterFailedException) {
            return false;
        }
    }

    private function acceptScalar(
        mixed $value,
        array|int $options,
        ?object $sentinel,
        bool $throwOnFailure,
        bool $nullOnFailure,
    ): bool {
        $result = filter_var($value, $this->filter, $options);

        if ($sentinel !== null && $result === $sentinel) {
            return false;
        }

        if ($throwOnFailure) {
            return true;
        }

        return $nullOnFailure ? $result !== null : $result !== false;
    }

    /**
     * Walk arrays in native depth-first input order without changing their
     * references or recursing through the native filter extension.
     */
    private function acceptArray(
        array $values,
        array|int $options,
        ?object $sentinel,
        bool $throwOnFailure,
        bool $nullOnFailure,
    ): bool {
        $stack = [['values' => $values, 'keys' => array_keys($values), 'position' => 0, 'reference' => null]];
        $activeReferences = [];
        $accepted = true;

        while ($stack !== []) {
            $last = array_key_last($stack);
            $position = $stack[$last]['position']++;

            if ($position === count($stack[$last]['keys'])) {
                $reference = $stack[$last]['reference'];

                if ($reference !== null) {
                    unset($activeReferences[$reference]);
                }

                array_pop($stack);
                continue;
            }

            $key = $stack[$last]['keys'][$position];
            $value = $stack[$last]['values'][$key];

            if (is_array($value)) {
                $reference = \ReflectionReference::fromArrayElement($stack[$last]['values'], $key)?->getId();

                if ($reference !== null) {
                    if (isset($activeReferences[$reference])) {
                        return false;
                    }

                    $activeReferences[$reference] = true;
                }

                $stack[] = ['values' => $value, 'keys' => array_keys($value), 'position' => 0, 'reference' => $reference];
                continue;
            }

            // Do not short-circuit ordinary false results: native array mode
            // evaluates every leaf, including stateful callback invocations.
            if (!$this->acceptScalar($value, $options, $sentinel, $throwOnFailure, $nullOnFailure)) {
                $accepted = false;
            }
        }

        return $accepted;
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

            return;
        }

        self::assertNativeOptionsSafe($filter, $options);
    }

    private static function assertNativeOptionsSafe(int $filter, array|int $options): void
    {
        $warning = null;
        set_error_handler(
            static function (int $severity, string $message) use (&$warning): bool {
                $warning ??= $message;

                return true;
            },
            E_WARNING,
        );

        try {
            $probe = self::nativeOptionProbe($filter);

            if ((self::flags($options) & FILTER_REQUIRE_ARRAY) !== 0) {
                $probe = [$probe];
            }

            try {
                filter_var($probe, $filter, $options);
            } catch (\Filter\FilterFailedException) {
                // The options are valid; only the representative probe failed validation.
            } catch (\ValueError|\TypeError $exception) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid filter_var options for filter %d', $filter),
                    previous: $exception,
                );
            }
        } finally {
            restore_error_handler();
        }

        if ($warning !== null) {
            throw new \InvalidArgumentException(
                sprintf('Invalid filter_var options for filter %d: %s', $filter, $warning),
            );
        }
    }

    private static function nativeOptionProbe(int $filter): string
    {
        return match ($filter) {
            FILTER_VALIDATE_INT => '0',
            FILTER_VALIDATE_BOOLEAN => 'true',
            FILTER_VALIDATE_FLOAT => '0.0',
            FILTER_VALIDATE_DOMAIN => 'example.com',
            FILTER_VALIDATE_URL => 'https://example.com',
            FILTER_VALIDATE_EMAIL => 'a@example.com',
            FILTER_VALIDATE_IP => '127.0.0.1',
            FILTER_VALIDATE_MAC => '00:00:00:00:00:00',
            default => '',
        };
    }

    private static function replaceNativeDefault(int $filter, array|int &$options): ?object
    {
        if ($filter === FILTER_CALLBACK
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
            && $filter !== FILTER_CALLBACK;
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

    private static function isValidRegexp(string $regexp): bool
    {
        $compileWarning = false;
        set_error_handler(
            static function () use (&$compileWarning): bool {
                $compileWarning = true;
                return true;
            },
            E_WARNING,
        );

        try {
            preg_match($regexp, '');

            return !$compileWarning;
        } finally {
            restore_error_handler();
        }
    }
}
