<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts ReflectionClass elements whose class name is in the allowed list.
 */
final class ReflectionConcreteClassFilter extends AbstractFilter
{
    public function __construct(
        private readonly array $allowedClassNames,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withAllowedClassNames(array $allowedClassNames): static
    {
        return new self($allowedClassNames, $this->iterable);
    }

    public function getAllowedClassNames(): array
    {
        return $this->allowedClassNames;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof \ReflectionClass) {
            return false;
        }

        return in_array($value->getName(), $this->allowedClassNames, true);
    }
}
