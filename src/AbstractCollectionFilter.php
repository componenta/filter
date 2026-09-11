<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Base implementation for iterable collection filters and operators.
 */
abstract class AbstractCollectionFilter implements CollectionFilterInterface
{
    public function __construct(
        protected iterable $iterable = []
    ) {
    }

    public function withIterable(iterable $iterable): static
    {
        $copy = clone $this;
        $copy->iterable = $iterable;

        return $copy;
    }

    public function toArray(bool $preserveKeys = false): array
    {
        return iterator_to_array($this->getIterator(), $preserveKeys);
    }
}
