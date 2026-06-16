<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that are subclasses of the specified parent class.
 */
final class SubclassFilter extends AbstractFilter
{
    public function __construct(
        private readonly string $parentClass,
        iterable $iterable = []
    ) {
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
        return is_subclass_of($value, $this->parentClass);
    }
}
