<?php

declare(strict_types=1);

use Componenta\Filter\MultipleOfFilter;

it('accepts decimal multiples despite floating-point representation noise', function (): void {
    $filter = new MultipleOfFilter(0.1);

    expect($filter->accept(0.3))->toBeTrue()
        ->and($filter->accept('0.3'))->toBeTrue()
        ->and($filter->accept(0.35))->toBeFalse();
});

dataset('invalid divisors', [
    'zero' => 0.0,
    'negative zero' => -0.0,
    'NaN' => NAN,
    'positive infinity' => INF,
    'negative infinity' => -INF,
]);

it('rejects invalid divisors', function (float $divisor): void {
    new MultipleOfFilter($divisor);
})->with('invalid divisors')->throws(InvalidArgumentException::class);
