<?php

declare(strict_types=1);

use Componenta\Filter\BetweenFilter;
use Componenta\Filter\GreaterThanEqualsFilter;
use Componenta\Filter\GreaterThanFilter;
use Componenta\Filter\LengthRangeFilter;
use Componenta\Filter\LessThanEqualsFilter;
use Componenta\Filter\LessThanFilter;
use Componenta\Filter\MaxLengthFilter;
use Componenta\Filter\MinLengthFilter;
use Componenta\Filter\RangeFilter;

dataset('invalid numeric filter configuration', [
    'between reversed bounds' => [static fn() => new BetweenFilter(2, 1)],
    'between NaN minimum' => [static fn() => new BetweenFilter(NAN, 1)],
    'between infinite maximum' => [static fn() => new BetweenFilter(0, INF)],
    'range reversed bounds' => [static fn() => new RangeFilter(2, 1)],
    'range infinite minimum' => [static fn() => new RangeFilter(-INF, 1)],
    'greater than NaN threshold' => [static fn() => new GreaterThanFilter(NAN)],
    'greater than or equal infinite threshold' => [static fn() => new GreaterThanEqualsFilter(INF)],
    'less than NaN threshold' => [static fn() => new LessThanFilter(NAN)],
    'less than or equal infinite threshold' => [static fn() => new LessThanEqualsFilter(-INF)],
]);

it('rejects invalid numeric filter configuration', function (Closure $factory): void {
    $factory();
})->with('invalid numeric filter configuration')->throws(InvalidArgumentException::class);

dataset('invalid length filter configuration', [
    'negative minimum length' => [static fn() => new MinLengthFilter(-1)],
    'negative maximum length' => [static fn() => new MaxLengthFilter(-1)],
    'negative range minimum' => [static fn() => new LengthRangeFilter(-1, 2)],
    'negative range maximum' => [static fn() => new LengthRangeFilter(0, -1)],
    'reversed length range' => [static fn() => new LengthRangeFilter(3, 2)],
]);

it('rejects invalid length filter configuration', function (Closure $factory): void {
    $factory();
})->with('invalid length filter configuration')->throws(InvalidArgumentException::class);

it('continues to accept valid boundary configuration', function (): void {
    expect((new BetweenFilter(1, 1))->accept(1))->toBeTrue()
        ->and((new RangeFilter(1, 1))->accept(1))->toBeTrue()
        ->and((new MinLengthFilter(0))->accept(''))->toBeTrue()
        ->and((new MaxLengthFilter(0))->accept(''))->toBeTrue()
        ->and((new LengthRangeFilter(0, 0))->accept(''))->toBeTrue();
});
