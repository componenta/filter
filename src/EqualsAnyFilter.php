<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that equal any of the expected values.
 */
final class EqualsAnyFilter extends AbstractFilter
{
    private readonly array $expected;

    public function __construct(
        array $expected,
        private readonly bool $strict = true,
        iterable $iterable = []
    ) {
        $this->expected = ConfigurationArray::copySlots($expected);
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
        return ValueComparator::contains($this->expected, $value, $this->strict);
    }
}
