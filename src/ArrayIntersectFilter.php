<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts array elements that have at least one value from the allowed list.
 */
final class ArrayIntersectFilter extends AbstractFilter
{
    public function __construct(
        private readonly array $allowed,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withAllowed(array $allowed): static
    {
        return new self($allowed, $this->iterable);
    }

    public function getAllowed(): array
    {
        return $this->allowed;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!is_array($value)) {
            return false;
        }

        return count(array_intersect($value, $this->allowed)) > 0;
    }
}
