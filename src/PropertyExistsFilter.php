<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements where the specified property exists.
 */
final class PropertyExistsFilter extends AbstractFilter
{
    public function __construct(
        private readonly string $property,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withProperty(string $property): static
    {
        return new self($property, $this->iterable);
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!is_object($value)) {
            return false;
        }

        return property_exists($value, $this->property);
    }
}
