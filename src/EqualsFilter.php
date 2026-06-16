<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that equal the expected value.
 */
final class EqualsFilter extends AbstractFilter
{
    public function __construct(
        private readonly mixed $expected,
        private readonly bool $strict = true,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withExpected(mixed $expected): static
    {
        return new self($expected, $this->strict, $this->iterable);
    }

    public function withStrict(bool $strict): static
    {
        return new self($this->expected, $strict, $this->iterable);
    }

    public function getExpected(): mixed
    {
        return $this->expected;
    }

    public function isStrict(): bool
    {
        return $this->strict;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return $this->strict
            ? $value === $this->expected
            : $value == $this->expected;
    }
}
