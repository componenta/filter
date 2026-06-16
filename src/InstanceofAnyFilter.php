<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements that are instances of any of the specified classes.
 */
final class InstanceofAnyFilter extends AbstractFilter
{
    public function __construct(
        private readonly array $classNames,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withClassNames(array $classNames): static
    {
        return new self($classNames, $this->iterable);
    }

    public function getClassNames(): array
    {
        return $this->classNames;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        foreach ($this->classNames as $className) {
            if ($value instanceof $className) {
                return true;
            }
        }

        return false;
    }
}
