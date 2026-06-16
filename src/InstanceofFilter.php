<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that are instances of the specified class.
 */
final class InstanceofFilter extends AbstractFilter
{
    public function __construct(
        private readonly string $className,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withClassName(string $className): static
    {
        return new self($className, $this->iterable);
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return $value instanceof $this->className;
    }
}
