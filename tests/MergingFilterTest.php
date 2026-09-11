<?php

declare(strict_types=1);

use Componenta\Filter\IntFilter;
use Componenta\Filter\MergingFilter;
use Componenta\Filter\PercentageFilter;
use Componenta\Filter\StringFilter;

it('concatenates arbitrary collection filters in order', function (): void {
    $filter = new MergingFilter(
        new IntFilter([1, 'one']),
        new PercentageFilter(50, ['a', 'b', 'c', 'd']),
    );

    expect($filter->toArray())->toBe([1, 'a', 'b']);
});

it('returns a new merge with the iterable applied to every inner filter', function (): void {
    $filter = new MergingFilter(
        new IntFilter([1, 'one']),
        new StringFilter([1, 'one']),
    );

    $changed = $filter->withIterable([2, 'two']);

    expect($changed)->not->toBe($filter)
        ->and($filter->toArray())->toBe([1, 'one'])
        ->and($changed->toArray())->toBe([2, 'two']);
});

it('does not consume a one-shot iterable until iteration starts', function (): void {
    $reads = 0;
    $source = (static function () use (&$reads): Generator {
        $reads++;
        yield 1;
        $reads++;
        yield 'two';
    })();

    $filter = (new MergingFilter(new IntFilter(), new StringFilter()))
        ->withIterable($source);

    expect($reads)->toBe(0);

    expect($filter->toArray())->toBe([1, 'two'])
        ->and($reads)->toBe(2);
});

it('replays a one-shot iterable for every merged filter and every traversal', function (): void {
    $source = (static function (): Generator {
        yield 'integer' => 2;
        yield 'string' => 'two';
    })();

    $filter = (new MergingFilter(new IntFilter(), new StringFilter()))
        ->withIterable($source);

    expect($filter->toArray(true))->toBe([
        'integer' => 2,
        'string' => 'two',
    ])->and($filter->toArray(true))->toBe([
        'integer' => 2,
        'string' => 'two',
    ]);
});

it('adds and removes collection filters immutably', function (): void {
    $integers = new IntFilter([1]);
    $strings = new StringFilter(['one']);
    $base = new MergingFilter($integers);
    $added = $base->withFilter($strings);
    $removed = $added->withoutFilter($integers);

    expect($base->getFilters())->toBe([$integers])
        ->and($added->getFilters())->toBe([$integers, $strings])
        ->and($removed->getFilters())->toBe([$strings]);
});
