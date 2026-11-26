<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose value is in the allowed values array.
 */
final class InArrayFilter extends AbstractFilter
{
    private readonly array $allowed;

    public function __construct(
        array $allowed,
        private readonly bool $strict = true,
        iterable $iterable = []
    ) {
        $this->allowed = ConfigurationArray::copySlots($allowed);
        parent::__construct($iterable);
    }

    public function withAllowed(array $allowed): static
    {
        return new self($allowed, $this->strict, $this->iterable);
    }

    public function withStrict(bool $strict): static
    {
        return new self($this->allowed, $strict, $this->iterable);
    }

    public function getAllowed(): array
    {
        return $this->allowed;
    }

    public function isStrict(): bool
    {
        return $this->strict;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return ValueComparator::contains($this->allowed, $value, $this->strict);
    }
}
