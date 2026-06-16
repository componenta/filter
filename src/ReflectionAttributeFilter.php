<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts reflection elements that have the specified attribute.
 */
final class ReflectionAttributeFilter extends AbstractFilter
{
    public function __construct(
        private readonly string $attributeName,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withAttributeName(string $attributeName): static
    {
        return new self($attributeName, $this->iterable);
    }

    public function getAttributeName(): string
    {
        return $this->attributeName;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof \ReflectionClass
            && !$value instanceof \ReflectionMethod
            && !$value instanceof \ReflectionFunction
            && !$value instanceof \ReflectionProperty
            && !$value instanceof \ReflectionParameter
            && !$value instanceof \ReflectionClassConstant
        ) {
            return false;
        }

        $attributes = $value->getAttributes($this->attributeName);
        return count($attributes) > 0;
    }
}
