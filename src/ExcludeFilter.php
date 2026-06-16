<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose value is NOT in the excluded values array.
 */
final class ExcludeFilter extends AbstractFilter
{
    public function __construct(
        private readonly array $excluded,
        private readonly bool $strict = true,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withExcluded(array $excluded): static
    {
        return new self($excluded, $this->strict, $this->iterable);
    }

    public function withStrict(bool $strict): static
    {
        return new self($this->excluded, $strict, $this->iterable);
    }

    public function getExcluded(): array
    {
        return $this->excluded;
    }

    public function isStrict(): bool
    {
        return $this->strict;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return !in_array($value, $this->excluded, $this->strict);
    }
}
