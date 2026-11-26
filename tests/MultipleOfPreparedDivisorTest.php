<?php

declare(strict_types=1);

use Componenta\Filter\MultipleOfFilter;

it('uses exact integer remainder semantics even at integer boundaries', function (): void {
    foreach ([PHP_INT_MIN, PHP_INT_MAX, -3, -1, 0, 1, 3] as $value) {
        foreach ([PHP_INT_MIN, PHP_INT_MAX, -3, -1, 1, 3] as $divisor) {
            expect((new MultipleOfFilter($divisor))->accept($value))->toBe($value % $divisor === 0);
        }
    }
});

it('preserves exact results before and after preparation and across immutable copies', function (): void {
    $filter = new MultipleOfFilter('0.000125');
    $values = ['0.000375', '0.000376', '0', '0.00125', '-0.00125', '0.000124', NAN, 'bad'];
    $expected = [true, false, true, true, true, false, false, false];

    for ($pass = 0; $pass < 3; $pass++) {
        expect(array_map($filter->accept(...), $values))->toBe($expected);
    }

    expect($filter->withIterable($values)->toArray())->toBe(['0.000375', '0', '0.00125', '-0.00125'])
        ->and($filter->withDivisor('0.000376')->accept('0.000376'))->toBeTrue()
        ->and($filter->accept('0.000376'))->toBeFalse();
});
