<?php

declare(strict_types=1);

namespace Componenta\Filter;

/**
 * Applies a predicate recursively to nested iterables.
 *
 * Object cycles are detected by identity. maxDepth additionally bounds array
 * recursion, where PHP does not expose a stable identity for recursive arrays.
 */
final class RecursiveFilter extends AbstractFilter
{
    public function __construct(
        private readonly PredicateInterface $filter,
        private readonly bool $yieldNestedAsArray = false,
        iterable $iterable = [],
        private readonly int $maxDepth = 64,
    ) {
        if ($maxDepth < 0) {
            throw new \InvalidArgumentException('Maximum recursive filter depth must be non-negative');
        }

        parent::__construct($iterable);
    }

    public function withFilter(PredicateInterface $filter): static
    {
        return new self($filter, $this->yieldNestedAsArray, $this->iterable, $this->maxDepth);
    }

    public function withYieldNestedAsArray(bool $yieldNestedAsArray): static
    {
        return new self($this->filter, $yieldNestedAsArray, $this->iterable, $this->maxDepth);
    }

    public function withMaxDepth(int $maxDepth): static
    {
        return new self($this->filter, $this->yieldNestedAsArray, $this->iterable, $maxDepth);
    }

    public function getFilter(): PredicateInterface
    {
        return $this->filter;
    }

    public function isYieldNestedAsArray(): bool
    {
        return $this->yieldNestedAsArray;
    }

    public function getMaxDepth(): int
    {
        return $this->maxDepth;
    }

    public function getIterator(): \Generator
    {
        yield from $this->iterate($this->iterable, 0, new \SplObjectStorage());
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return $this->filter->accept($value, $key);
    }

    /**
     * @param \SplObjectStorage<object, null> $active
     */
    private function iterate(iterable $iterable, int $depth, \SplObjectStorage $active): \Generator
    {
        $trackedObjects = [];

        try {
            while ($iterable instanceof \IteratorAggregate) {
                $this->trackIterableObject($iterable, $active, $trackedObjects);
                $iterable = $iterable->getIterator();
            }

            if (is_object($iterable)) {
                $this->trackIterableObject($iterable, $active, $trackedObjects);
            }

            foreach ($iterable as $key => $value) {
                $key = self::predicateKey($key);

                if (!is_iterable($value)) {
                    if ($this->accept($value, $key)) {
                        yield $key => $value;
                    }

                    continue;
                }

                if ($depth >= $this->maxDepth) {
                    throw new \OverflowException(sprintf(
                        'Maximum recursive filter depth of %d exceeded',
                        $this->maxDepth,
                    ));
                }

                $nested = $this->iterate($value, $depth + 1, $active);

                if ($this->yieldNestedAsArray) {
                    yield $key => iterator_to_array($nested, true);
                } else {
                    yield from $nested;
                }
            }
        } finally {
            foreach ($trackedObjects as $trackedObject) {
                $active->offsetUnset($trackedObject);
            }
        }
    }

    /**
     * @param \SplObjectStorage<object, null> $active
     * @param list<object> $trackedObjects
     */
    private function trackIterableObject(
        object $iterable,
        \SplObjectStorage $active,
        array &$trackedObjects,
    ): void {
        if ($active->offsetExists($iterable)) {
            throw new \RuntimeException('Recursive iterable cycle detected');
        }

        $active->offsetSet($iterable, null);
        $trackedObjects[] = $iterable;
    }
}
