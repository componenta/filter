<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts array elements that have no values from the excluded list.
 */
final class ArrayDiffFilter extends AbstractFilter
{
    private readonly array $excluded;
    private readonly ?array $scalarIndex;

    public function __construct(
        array $excluded,
        iterable $iterable = []
    ) {
        $this->excluded = ConfigurationArray::copySlots($excluded);
        $this->scalarIndex = ArrayValueComparator::prepareScalarIndex($this->excluded);
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

        foreach ($value as $item) {
            if (ArrayValueComparator::contains($this->excluded, $item, $this->scalarIndex)) {
                return false;
            }
        }

        return true;
    }
}
