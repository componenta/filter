<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose value is NOT in the excluded values array.
 */
final class ExcludeFilter extends AbstractFilter
{
    private readonly array $excluded;

    public function __construct(
        array $excluded,
        private readonly bool $strict = true,
        iterable $iterable = []
    ) {
        $this->excluded = ConfigurationArray::copySlots($excluded);
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
        return !ValueComparator::contains($this->excluded, $value, $this->strict);
    }
}
