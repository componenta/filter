<?php

declare(strict_types=1);

use Componenta\Filter\IntFilter;
use Componenta\Filter\RecursiveFilter;

it('bounds chains of fresh aggregate objects that never terminate', function (): void {
    $source = new class implements IteratorAggregate {
        public function getIterator(): Traversable
        {
            return new self();
        }
    };

    $filter = new RecursiveFilter(new IntFilter(), iterable: $source, maxAggregateDepth: 3);

    expect(fn() => $filter->toArray())->toThrow(OverflowException::class, 'Maximum iterator aggregate depth of 3 exceeded');
});

it('does not count aggregate wrappers as nested collection values', function (): void {
    $source = new class implements IteratorAggregate {
        public function getIterator(): Traversable
        {
            return new ArrayIterator([1, 'two', 3]);
        }
    };
    $filter = new RecursiveFilter(new IntFilter(), iterable: $source, maxDepth: 0, maxAggregateDepth: 1);

    expect($filter->toArray())->toBe([1, 3])
        ->and($filter->withFilter(new IntFilter())->getMaxAggregateDepth())->toBe(1)
        ->and($filter->withYieldNestedAsArray(true)->getMaxAggregateDepth())->toBe(1)
        ->and($filter->withMaxDepth(2)->getMaxAggregateDepth())->toBe(1)
        ->and($filter->withIterable([1])->getMaxAggregateDepth())->toBe(1)
        ->and(fn() => $filter->withMaxAggregateDepth(0)->toArray())->toThrow(OverflowException::class)
        ->and(fn() => $filter->withMaxAggregateDepth(-1))->toThrow(InvalidArgumentException::class);
});
