<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements where the specified property equals the expected value.
 */
final class PropertyEqualsFilter extends AbstractFilter
{
    public function __construct(
        private readonly string $property,
        private readonly mixed $expected,
        private readonly bool $strict = true,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withProperty(string $property): static
    {
        return new self($property, $this->expected, $this->strict, $this->iterable);
    }

    public function withExpected(mixed $expected): static
    {
        return new self($this->property, $expected, $this->strict, $this->iterable);
    }

    public function withStrict(bool $strict): static
    {
        return new self($this->property, $this->expected, $strict, $this->iterable);
    }

    public function getProperty(): string
    {
        return $this->property;
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
        if (!is_object($value)) {
            return false;
        }

        if (!property_exists($value, $this->property)) {
            return false;
        }

        $propertyValue = $value->{$this->property};

        return $this->strict
            ? $propertyValue === $this->expected
            : $propertyValue == $this->expected;
    }
}
