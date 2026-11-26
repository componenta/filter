<?php

declare(strict_types=1);

use Componenta\Filter\PercentageFilter;

it('uses the exact decimal floor for high precision percentages away from and near integer boundaries', function (): void {
    // Expected values are floors of the explicit decimal percentages,
    // calculated with integer arithmetic independently of the filter.
    $cases = [
        [100, 29.512345678901234, 29],
        [1000, 29.512345678901234, 295],
        [10000, 29.512345678901234, 2951],
        [10000, 0.009999999999999998, 0],
        [10000, 0.01, 1],
        [10000, 0.010000000000000002, 1],
        [10000, 99.99999999999999, 9999],
        [9999, 100.0, 9999],
        [1000, 29.499999999999996, 294],
        [1000, 29.500000000000004, 295],
        [10000, 42.42424242424242, 4242],
        [10000, 5.0e-324, 0],
    ];

    foreach ($cases as [$count, $percentage, $expected]) {
        $values = range(1, $count);
        $filter = new PercentageFilter($percentage, $values);
        $generator = static fn(): Generator => yield from $values;

        expect(count($filter->toArray()))->toBe($expected)
            ->and(count($filter->toArray()))->toBe($expected)
            ->and(count($filter->withIterable($generator())->toArray()))->toBe($expected);
    }
});

it('retains exact floors for large advertised counts when the output is small', function (): void {
    $source = new class implements IteratorAggregate, Countable {
        public function count(): int
        {
            return PHP_INT_MAX;
        }

        public function getIterator(): Traversable
        {
            yield from range(1, 100);
        }
    };

    // Independently computed with exact decimal arithmetic for 2^63 - 1.
    foreach ([
        [1.0842021724855044e-17, 0],
        [1.0842021724855046e-17, 1],
        [2.168404344971009e-17, 2],
        [2.5e-17, 2],
        [5e-17, 4],
        [1.2345678901234568e-16, 11],
        [1e-15, 92],
    ] as [$percentage, $expected]) {
        expect(count((new PercentageFilter($percentage, $source))->toArray()))->toBe($expected);
    }
})->skip(PHP_INT_SIZE !== 8, 'Requires 64-bit integer count');
