<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose concrete class exactly matches any of the allowed class names.
 */
final class AnyClassFilter extends AbstractFilter
{
    private readonly array $allowedClasses;

    public function __construct(
        array $allowedClasses,
        iterable $iterable = []
    ) {
        $this->allowedClasses = StringList::normalize($allowedClasses, 'allowedClasses');
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
        return is_object($value)
            && ConcreteClassName::contains($this->allowedClasses, $value::class);
    }
}
