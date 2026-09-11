<?php

declare(strict_types=1);

use Componenta\Filter\IntFilter;
use Componenta\Filter\MergingFilter;
use Componenta\Filter\StringFilter;

it('accepts values accepted by at least one merged filter', function (): void {
    $filter = new MergingFilter(new IntFilter(), new StringFilter());

    expect($filter->accept(1))->toBeTrue()
        ->and($filter->accept('one'))->toBeTrue()
        ->and($filter->accept([]))->toBeFalse()
        ->and((new MergingFilter())->accept('anything'))->toBeFalse();
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

it('replays a one-shot iterable for every merged filter', function (): void {
    $source = (static function (): Generator {
        yield 'integer' => 2;
        yield 'string' => 'two';
    })();

    $filter = (new MergingFilter(new IntFilter(), new StringFilter()))
        ->withIterable($source);

    expect($filter->toArray(true))->toBe([
        'integer' => 2,
        'string' => 'two',
    ]);
});
