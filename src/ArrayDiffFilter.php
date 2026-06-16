<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts array elements that have no values from the excluded list.
 */
final class ArrayDiffFilter extends AbstractFilter
{
    public function __construct(
        private readonly array $excluded,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withExcluded(array $excluded): static
    {
        return new self($excluded, $this->iterable);
    }

    public function getExcluded(): array
    {
        return $this->excluded;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!is_array($value)) {
            return false;
        }

        return array_diff($value, $this->excluded) == $value;
    }
}
