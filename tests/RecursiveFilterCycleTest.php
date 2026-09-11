<?php

declare(strict_types=1);

use Componenta\Filter\RecursiveFilter;
use Componenta\Filter\StringFilter;

it('rejects recursive iterable object cycles deterministically', function (): void {
    $cycle = new class implements IteratorAggregate {
        public function getIterator(): Traversable
        {
            yield 'value' => 'accepted';
            yield 'self' => $this;
        }
    };

    $filter = new RecursiveFilter(new StringFilter(), iterable: $cycle);

    expect(fn() => iterator_to_array($filter->getIterator(), false))
        ->toThrow(RuntimeException::class, 'Recursive iterable cycle detected');
});

it('limits recursive array depth instead of recursing indefinitely', function (): void {
    $value = 'accepted';

    for ($i = 0; $i < 4; $i++) {
        $value = [$value];
    }

    $filter = new RecursiveFilter(
        new StringFilter(),
        iterable: [$value],
        maxDepth: 2,
    );

    expect(fn() => iterator_to_array($filter->getIterator(), false))
        ->toThrow(OverflowException::class, 'Maximum recursive filter depth of 2 exceeded');
});

it('supports explicitly configured finite recursive depth', function (): void {
    $filter = new RecursiveFilter(
        new StringFilter(),
        iterable: [[['one', 2, 'three']]],
        maxDepth: 2,
    );

    expect(iterator_to_array($filter->getIterator(), false))->toBe(['one', 'three']);
});

it('rejects negative maximum depth', function (): void {
    new RecursiveFilter(new StringFilter(), maxDepth: -1);
})->throws(InvalidArgumentException::class);
