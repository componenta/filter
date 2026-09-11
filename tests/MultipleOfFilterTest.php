<?php

declare(strict_types=1);

use Componenta\Filter\MultipleOfFilter;

it('accepts decimal multiples despite floating-point representation noise', function (): void {
    $filter = new MultipleOfFilter(0.1);

    expect($filter->accept(0.3))->toBeTrue()
        ->and($filter->accept('0.3'))->toBeTrue()
        ->and($filter->accept(0.30000000000000004))->toBeTrue()
        ->and($filter->accept(-0.30000000000000004))->toBeTrue()
        ->and($filter->accept(0.35))->toBeFalse();
});

it('does not let floating-point tolerance swallow a real fractional remainder', function (): void {
    $filter = new MultipleOfFilter(1.0);

    expect($filter->accept(1_000_000_000_000_000.25))->toBeFalse();
});

it('checks integer and numeric-string multiples without integer or float overflow', function (): void {
    $filter = new MultipleOfFilter('7');

    expect($filter->accept('864197523086419752308641975230'))->toBeTrue()
        ->and($filter->accept('864197523086419752308641975231'))->toBeFalse();
});

it('checks exact decimal numeric strings without floating-point rounding', function (): void {
    expect((new MultipleOfFilter('0.1'))->accept('0.3'))->toBeTrue()
        ->and((new MultipleOfFilter('0.1'))->accept('0.35'))->toBeFalse()
        ->and((new MultipleOfFilter('0.0000000000000000000001'))->accept('0.0000000000000000000003'))->toBeTrue();
});

it('supports exact scientific notation with very large exponents', function (): void {
    $filter = new MultipleOfFilter('0.2');

    expect($filter->accept('1e100000000000000000000'))->toBeTrue()
        ->and((new MultipleOfFilter('4e100'))->accept('1e101'))->toBeFalse();
});

dataset('invalid divisors', [
    'zero' => 0.0,
    'negative zero' => -0.0,
    'NaN' => NAN,
    'positive infinity' => INF,
    'negative infinity' => -INF,
    'non numeric string' => 'not-a-number',
]);

it('rejects invalid divisors', function (int|float|string $divisor): void {
    new MultipleOfFilter($divisor);
})->with('invalid divisors')->throws(InvalidArgumentException::class);
