<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts elements whose value is in the allowed values array.
 */
final class InArrayFilter extends AbstractFilter
{
    public function __construct(
        private readonly array $allowed,
        private readonly bool $strict = true,
        iterable $iterable = []
    ) {
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
        return in_array($value, $this->allowed, $this->strict);
    }
}
