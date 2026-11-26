<?php

declare(strict_types=1);

use Componenta\Filter\PercentageFilter;

it('floors decimal percentages consistently across supported iterable forms', function (): void {
    $values = range(1, 100);

    foreach (range(0, 100) as $percentage) {
        $expected = array_slice($values, 0, $percentage);
        $generator = (static function () use ($values): Generator {
            yield from $values;
        })();

        expect((new PercentageFilter($percentage, $values))->toArray())->toBe($expected)
            ->and((new PercentageFilter($percentage, new ArrayIterator($values)))->toArray())->toBe($expected)
            ->and((new PercentageFilter($percentage, $generator))->toArray())->toBe($expected);
    }
});

it('preserves fractional boundaries without an arbitrary epsilon', function (): void {
    $values = range(1, 1000);

    expect((new PercentageFilter(2.9, $values))->toArray())->toBe(range(1, 29))
        ->and((new PercentageFilter(2.8999999999999995, $values))->toArray())->toBe(range(1, 28))
        ->and((new PercentageFilter(2.9000000000000004, $values))->toArray())->toBe(range(1, 29))
        ->and((new PercentageFilter(0.0000000000000001, $values))->toArray())->toBe([]);
});
