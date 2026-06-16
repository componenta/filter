<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Applies a filter recursively to nested iterables.
 */
final class RecursiveFilter extends AbstractFilter
{
    public function __construct(
        private readonly FilterInterface $filter,
        private readonly bool $yieldNestedAsArray = false,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function withFilter(FilterInterface $filter): static
    {
        return new self($filter, $this->yieldNestedAsArray, $this->iterable);
    }

    public function withYieldNestedAsArray(bool $yieldNestedAsArray): static
    {
        return new self($this->filter, $yieldNestedAsArray, $this->iterable);
    }

    public function getFilter(): FilterInterface
    {
        return $this->filter;
    }

    public function isYieldNestedAsArray(): bool
    {
        return $this->yieldNestedAsArray;
    }

    public function getIterator(): \Generator
    {
        foreach ($this->iterable as $key => $value) {
            if (is_iterable($value)) {
                $nested = $this->withIterable($value);

                if ($this->yieldNestedAsArray) {
                    yield $key => $nested->toArray();
                } else {
                    yield from $nested->getIterator();
                }
            } elseif ($this->accept($value, $key)) {
                yield $key => $value;
            }
        }
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return $this->filter->accept($value, $key);
    }
}
