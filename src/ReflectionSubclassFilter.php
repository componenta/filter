<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts ReflectionClass elements that are subclasses of the specified class or implement the specified interface.
 */
final class ReflectionSubclassFilter extends AbstractFilter
{
    public function __construct(
        private readonly string $parentClass,
        iterable $iterable = []
    ) {
        if (!class_exists($parentClass) && !interface_exists($parentClass)) {
            throw new \InvalidArgumentException(sprintf('Unknown parent class or interface: %s', $parentClass));
        }

        parent::__construct($iterable);
    }

    public function withParentClass(string $parentClass): static
    {
        return new self($parentClass, $this->iterable);
    }

    public function getParentClass(): string
    {
        return $this->parentClass;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof \ReflectionClass) {
            return false;
        }

        return $value->isSubclassOf($this->parentClass);
    }
}
