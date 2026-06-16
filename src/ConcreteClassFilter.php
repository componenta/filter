<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose concrete class exactly matches the specified class name.
 */
final class ConcreteClassFilter extends AbstractFilter
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
        if (!is_object($value)) {
            return false;
        }

        return $value::class === $this->className;
    }
}
