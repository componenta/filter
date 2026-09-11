<?php

declare(strict_types=1);

use Componenta\Filter\PercentageFilter;

it('keeps the requested percentage from the beginning of an array', function (): void {
    $filter = new PercentageFilter(50, ['a', 'b', 'c', 'd']);

    expect($filter->toArray())->toBe(['a', 'b']);
});

it('does not collapse repeated generator keys before calculating the percentage', function (): void {
    $source = (static function (): Generator {
        yield 'same' => 'a';
        yield 'same' => 'b';
        yield 'third' => 'c';
        yield 'fourth' => 'd';
    })();

    $filter = new PercentageFilter(50, $source);

    expect(iterator_to_array($filter->getIterator(), false))->toBe(['a', 'b']);
});

it('fails fast when used as a standalone predicate', function (): void {
    (new PercentageFilter(50))->accept('value');
})->throws(LogicException::class);

dataset('invalid percentages', [
    'below zero' => -0.1,
    'above one hundred' => 100.1,
    'NaN' => NAN,
    'positive infinity' => INF,
    'negative infinity' => -INF,
]);

it('rejects invalid percentage configuration', function (float $percentage): void {
    new PercentageFilter($percentage);
})->with('invalid percentages')->throws(InvalidArgumentException::class);
