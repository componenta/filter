<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that equal any of the expected values.
 */
final class EqualsAnyFilter extends AbstractFilter
{
    public function __construct(
        private readonly array $expected,
        private readonly bool $strict = true,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withExpected(array $expected): static
    {
        return new self($expected, $this->strict, $this->iterable);
    }

    public function withStrict(bool $strict): static
    {
        return new self($this->expected, $strict, $this->iterable);
    }

    public function getExpected(): array
    {
        return $this->expected;
    }

    public function isStrict(): bool
    {
        return $this->strict;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return in_array($value, $this->expected, $this->strict);
    }
}
