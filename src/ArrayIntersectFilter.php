<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Accepts array elements that have at least one value from the allowed list.
 */
final class ArrayIntersectFilter extends AbstractFilter
{
    private readonly array $allowed;
    private readonly ?array $scalarIndex;

    public function __construct(
        array $allowed,
        iterable $iterable = []
    ) {
        $this->allowed = ConfigurationArray::copySlots($allowed);
        $this->scalarIndex = ArrayValueComparator::prepareScalarIndex($this->allowed);
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

        foreach ($value as $item) {
            if (ArrayValueComparator::contains($this->allowed, $item, $this->scalarIndex)) {
                return true;
            }
        }

        return false;
    }
}
