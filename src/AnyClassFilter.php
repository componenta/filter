<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose concrete class exactly matches any of the allowed class names.
 */
final class AnyClassFilter extends AbstractFilter
{
    public function __construct(
        private readonly array $allowedClasses,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withAllowedClasses(array $allowedClasses): static
    {
        return new self($allowedClasses, $this->iterable);
    }

    public function getAllowedClasses(): array
    {
        return $this->allowedClasses;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!is_object($value)) {
            return false;
        }

        return in_array($value::class, $this->allowedClasses, true);
    }
}
