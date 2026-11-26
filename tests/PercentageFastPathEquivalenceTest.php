<?php

declare(strict_types=1);

use Componenta\Filter\PercentageFilter;

it('uses the shortest decimal semantics for common percentages and rejects adjacent lower floats', function (): void {
    $values = range(1, 10000);

    foreach ([
        [29.5, 2950],
        [29.499999999999996, 2949],
        [29.500000000000004, 2950],
        [0.1, 10],
        [0.09999999999999999, 9],
        [0.10000000000000002, 10],
        [1.23, 123],
        [1.2299999999999998, 122],
        [1.2345, 123],
        [0.01, 1],
    ] as [$percentage, $expected]) {
        $filter = new PercentageFilter($percentage, $values);
        expect(count($filter->toArray()))->toBe($expected)
            ->and(count($filter->toArray()))->toBe($expected);
    }
});
