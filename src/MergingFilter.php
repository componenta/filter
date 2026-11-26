<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Concatenates results from multiple collection filters.
 *
 * This is a collection operator, not a predicate. Each inner filter operates
 * on its own iterable source unless withIterable() is used to replace all
 * sources at once.
 */
class MergingFilter implements CollectionFilterInterface
{
    /** @var CollectionFilterInterface[] */
    private array $filters = [];

    public function __construct(CollectionFilterInterface ...$filters)
    {
        $this->filters = $filters;
    }

    public function withFilter(CollectionFilterInterface $filter): static
    {
        $copy = clone $this;
        $copy->filters[] = $filter;

        return $copy;
    }

    public function withoutFilter(CollectionFilterInterface $filter): static
    {
        $copy = clone $this;
        $copy->filters = array_values(
            array_filter(
                $this->filters,
                static fn(CollectionFilterInterface $candidate): bool => $candidate !== $filter,
            ),
        );

        return $copy;
    }

    /** @return CollectionFilterInterface[] */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getIterator(): \Generator
    {
        foreach ($this->filters as $filter) {
            yield from $filter->getIterator();
        }
    }

    public function withIterable(iterable $iterable): static
    {
        $source = is_array($iterable)
            ? $iterable
            : new ReplayableSource($iterable);

        $copy = clone $this;
        $copy->filters = array_map(
            static fn(CollectionFilterInterface $filter): CollectionFilterInterface => $filter->withIterable($source),
            $this->filters,
        );

        return $copy;
    }

    public function toArray(bool $preserveKeys = false): array
    {
        return CollectionArray::from($this->getIterator(), $preserveKeys);
    }
}
